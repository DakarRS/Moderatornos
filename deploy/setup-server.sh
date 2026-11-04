#!/bin/bash
# Prepares a fresh Ubuntu server. Safe to run again: existing secrets are kept.
# Usage: setup-server.sh <site address> <deploy public key>
set -euo pipefail

site="${1:?site address}"
deploy_key="${2:?deploy public key}"
here="$(cd "$(dirname "$0")" && pwd)"
export DEBIAN_FRONTEND=noninteractive

apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq ca-certificates curl ufw unattended-upgrades restic
command -v docker >/dev/null || curl -fsSL https://get.docker.com | sh

cat > /etc/apt/apt.conf.d/52moderatornos <<'CONF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
Unattended-Upgrade::Automatic-Reboot "true";
Unattended-Upgrade::Automatic-Reboot-Time "04:30";
CONF

ufw default deny incoming >/dev/null
ufw default allow outgoing >/dev/null
ufw allow OpenSSH >/dev/null
ufw allow 80/tcp >/dev/null
ufw allow 443 >/dev/null
ufw --force enable >/dev/null

printf 'PasswordAuthentication no\nKbdInteractiveAuthentication no\nPermitRootLogin prohibit-password\n' > /etc/ssh/sshd_config.d/10-moderatornos.conf
sshd -t
systemctl reload ssh

id deploy >/dev/null 2>&1 || useradd --create-home --shell /bin/bash deploy
usermod -aG docker deploy
install -m 755 "$here/deploy.sh" /usr/local/bin/moderatornos-deploy
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
# This key can only run the deploy script: no shell, no forwarding.
echo "restrict,command=\"/usr/local/bin/moderatornos-deploy\" $deploy_key" > /home/deploy/.ssh/authorized_keys
chown deploy:deploy /home/deploy/.ssh/authorized_keys
chmod 600 /home/deploy/.ssh/authorized_keys

install -d -m 750 -o deploy -g deploy /opt/moderatornos
cd /opt/moderatornos
if [ ! -f .env ]; then
  umask 077
  cat > .env <<ENV
APP_NAME=Moderatornos
APP_KEY=base64:$(openssl rand -base64 32)
APP_URL=https://$site
SITE_ADDRESS=$site
DB_PASSWORD=$(openssl rand -hex 32)
ENV
  chown deploy:deploy .env
fi
if [ ! -f backup.env ]; then
  umask 077
  cat > backup.env <<ENV
RESTIC_REPOSITORY=/var/backups/moderatornos-restic
RESTIC_PASSWORD=$(openssl rand -hex 32)
ENV
fi
chown root:root backup.env
chmod 600 backup.env

install -m 755 "$here/backup.sh" /usr/local/bin/moderatornos-backup
cat > /etc/systemd/system/moderatornos-backup.service <<'UNIT'
[Unit]
Description=Moderatornos backup
[Service]
Type=oneshot
ExecStart=/usr/local/bin/moderatornos-backup
UNIT
cat > /etc/systemd/system/moderatornos-backup.timer <<'UNIT'
[Unit]
Description=Nightly Moderatornos backup
[Timer]
OnCalendar=*-*-* 03:30:00
RandomizedDelaySec=15m
Persistent=true
[Install]
WantedBy=timers.target
UNIT
systemctl daemon-reload
systemctl enable --now moderatornos-backup.timer >/dev/null
echo "setup complete"

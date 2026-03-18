#!/usr/bin/env bash
set -Eeuo pipefail

HOST="${1:-}"
PORT="${2:-22}"

if [ -z "$HOST" ]; then
  echo "Uso: $0 <host> [porta]"
  exit 1
fi

REMOTE_ADMIN="${REMOTE_ADMIN:-root}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"

UPGRADE_SYSTEM="${UPGRADE_SYSTEM:-1}"
INSTALL_DOCKER="${INSTALL_DOCKER:-1}"
INSTALL_BASE_PACKAGES="${INSTALL_BASE_PACKAGES:-1}"
CONFIGURE_GIT="${CONFIGURE_GIT:-1}"
TEST_DOCKER="${TEST_DOCKER:-1}"

REMOTE_TARGET="${REMOTE_ADMIN}@${HOST}"

log() {
  printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "Erro: comando obrigatório não encontrado: $1"
    exit 1
  }
}

require_cmd ssh

#log "Conectando em ${REMOTE_TARGET}:${PORT}"
#
#ssh -p "$PORT" \
#  -o BatchMode=no \
#  -o StrictHostKeyChecking=accept-new \
#  "$REMOTE_TARGET" "echo conectado" >/dev/null

read -r -d '' REMOTE_SCRIPT <<'EOS' || true
set -Eeuo pipefail

DEPLOY_USER="$1"
UPGRADE_SYSTEM="$2"
INSTALL_BASE_PACKAGES="$3"
CONFIGURE_GIT="$4"
INSTALL_DOCKER="$5"
TEST_DOCKER="$6"

log() {
  printf '\n[REMOTE %s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

export DEBIAN_FRONTEND=noninteractive

if [ "$(id -u)" -ne 0 ]; then
  echo "Erro: script remoto precisa ser root"
  exit 1
fi

log "Iniciando setup do servidor"

if [ "$UPGRADE_SYSTEM" = "1" ]; then
  log "apt update"
  apt-get update

  log "apt upgrade"
  apt-get upgrade -y
fi

if [ "$INSTALL_BASE_PACKAGES" = "1" ]; then
  log "Instalando curl, git"
  apt-get install -y curl git ca-certificates gnupg
fi

if [ "$CONFIGURE_GIT" = "1" ]; then
  log "Configurando git global"
  git config --global submodule.recurse true
fi

if [ "$INSTALL_DOCKER" = "1" ]; then
  log "Instalando Docker"
  curl -fsSL https://get.docker.com | sh
fi

if ! id -u "$DEPLOY_USER" >/dev/null 2>&1; then
  log "Criando usuário deploy"
  useradd -m -s /bin/bash "$DEPLOY_USER"
fi

log "Bloqueando senha do deploy"
passwd -l "$DEPLOY_USER" || true

if getent group docker >/dev/null 2>&1; then
  log "Adicionando deploy ao grupo docker"
  usermod -aG docker "$DEPLOY_USER"
fi

mkdir -p /srv/stacks
chown -R "$DEPLOY_USER:$DEPLOY_USER" /srv/stacks
find /srv/stacks -type d -exec chmod 755 {} \;
find /srv/stacks -type f -exec chmod 644 {} \;

if command -v systemctl >/dev/null 2>&1; then
  systemctl enable docker || true
  systemctl restart docker || true
fi

if [ "$TEST_DOCKER" = "1" ]; then
  log "Testando Docker"
  docker run --rm hello-world
fi

echo ""
echo "===================================="
echo "Servidor configurado com sucesso"
echo "Usuário deploy pronto"
echo "===================================="
EOS

log "Executando setup remoto"

ssh -p "$PORT" \
  -o BatchMode=no \
  -o StrictHostKeyChecking=accept-new \
  "$REMOTE_TARGET" \
  "bash -s" -- \
  "$DEPLOY_USER" \
  "$UPGRADE_SYSTEM" \
  "$INSTALL_BASE_PACKAGES" \
  "$CONFIGURE_GIT" \
  "$INSTALL_DOCKER" \
  "$TEST_DOCKER" \
  <<< "$REMOTE_SCRIPT"

log "Setup concluído com sucesso"

cat <<EOF

==============================================
Próximos passos
==============================================

1. Criar acesso SSH do deploy:
    ./.docker/scripts/setup-deploy.sh ${HOST} ${PORT} ${DEPLOY_USER}

2. Testar:
    ssh ${DEPLOY_USER}@${HOST}

4. Realizar o bootstrap do projeto:
    php vendor/bin/envoy run bootstrap --servers=${DEPLOY_USER}@${HOST}

5. Executar bootstrap:
  ./.docker/scripts/bootstrap.sh <tag>



3. Deploy:
   docker compose up -d

EOF

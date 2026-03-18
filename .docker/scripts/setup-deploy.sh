#!/usr/bin/env bash
set -Eeuo pipefail

# Example:
# REMOTE_ADMIN=root ./setup-deploy.sh matrix.bnweb.org 2221 deploy

HOST="${1:-}"
PORT="${2:-22}"
DEPLOY_USER="${3:-deploy}"

if [ -z "$HOST" ]; then
  echo "Uso: $0 <host> [porta] [usuario_deploy]"
  echo "Exemplo: $0 matrix.bnweb.org 2221 deploy"
  exit 1
fi

REMOTE_ADMIN="${REMOTE_ADMIN:-root}"
REMOTE_USE_SUDO="${REMOTE_USE_SUDO:-auto}"
SETUP_STACKS_DIR="${SETUP_STACKS_DIR:-1}"
STACKS_DIR="${STACKS_DIR:-/srv/stacks}"
ADD_TO_DOCKER_GROUP="${ADD_TO_DOCKER_GROUP:-1}"

SSH_ALIAS="${DEPLOY_USER}_${HOST//./_}"
SSH_DIR="$HOME/.ssh"
SSH_KEY="$SSH_DIR/id_${SSH_ALIAS}"
SSH_PUB_KEY="${SSH_KEY}.pub"
SSH_CONFIG="$SSH_DIR/config"
SSH_COMMENT="${DEPLOY_USER}@${HOST}"

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
require_cmd ssh-keygen
require_cmd grep
require_cmd cat
require_cmd mktemp

mkdir -p "$SSH_DIR"
chmod 700 "$SSH_DIR"

if [ ! -f "$SSH_KEY" ]; then
  log "Gerando chave SSH: $SSH_KEY"
  ssh-keygen -t ed25519 -C "$SSH_COMMENT" -f "$SSH_KEY" -N ""
else
  log "Chave SSH já existe: $SSH_KEY"
fi

touch "$SSH_CONFIG"
chmod 600 "$SSH_CONFIG"

if ! grep -q "^Host $SSH_ALIAS$" "$SSH_CONFIG" 2>/dev/null; then
  log "Adicionando alias SSH ao ~/.ssh/config: $SSH_ALIAS"
  cat >> "$SSH_CONFIG" <<EOF

Host $HOST
    HostName $HOST
    Port $PORT
    IdentityFile $SSH_KEY
    IdentitiesOnly yes
    ServerAliveInterval 60
    ServerAliveCountMax 3
EOF
else
  log "Alias SSH já existe no config: $SSH_ALIAS"
fi

chmod 600 "$SSH_KEY"
chmod 644 "$SSH_PUB_KEY"

PUB_KEY_B64="$(base64 < "$SSH_PUB_KEY" | tr -d '\n')"

REMOTE_TARGET="${REMOTE_ADMIN}@${HOST}"

#log "Validando acesso ao servidor com ${REMOTE_TARGET}:${PORT}"
#ssh -p "$PORT" \
#  -o BatchMode=no \
#  -o StrictHostKeyChecking=accept-new \
#  "$REMOTE_TARGET" "echo ok" >/dev/null

read -r -d '' REMOTE_SCRIPT <<'EOS' || true
set -Eeuo pipefail

DEPLOY_USER="$1"
PUB_KEY_CONTENT="$(printf '%s' "$2" | base64 -d)"
REMOTE_USE_SUDO="$3"
SETUP_STACKS_DIR="$4"
STACKS_DIR="$5"
ADD_TO_DOCKER_GROUP="$6"

if [ "$REMOTE_USE_SUDO" = "always" ]; then
  SUDO="sudo"
elif [ "$REMOTE_USE_SUDO" = "never" ]; then
  SUDO=""
else
  if [ "$(id -u)" -eq 0 ]; then
    SUDO=""
  elif command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
  else
    echo "Erro: usuário remoto não é root e sudo não está disponível."
    exit 1
  fi
fi

run() {
  if [ -n "$SUDO" ]; then
    $SUDO "$@"
  else
    "$@"
  fi
}

if ! id -u "$DEPLOY_USER" >/dev/null 2>&1; then
  run useradd -m -s /bin/bash "$DEPLOY_USER"
fi

HOME_DIR="$(getent passwd "$DEPLOY_USER" | cut -d: -f6)"
SSH_DIR="$HOME_DIR/.ssh"
AUTHORIZED_KEYS="$SSH_DIR/authorized_keys"
KNOWN_HOSTS="$SSH_DIR/known_hosts"

run mkdir -p "$SSH_DIR"
run touch "$AUTHORIZED_KEYS"
run touch "$KNOWN_HOSTS"
run chmod 700 "$SSH_DIR"
run chmod 600 "$AUTHORIZED_KEYS"
run chmod 644 $KNOWN_HOSTS

run ssh-keyscan -H github.com >> $KNOWN_HOSTS 2>/dev/null

if ! run grep -qxF "$PUB_KEY_CONTENT" "$AUTHORIZED_KEYS"; then
  printf '%s\n' "$PUB_KEY_CONTENT" | run tee -a "$AUTHORIZED_KEYS" >/dev/null
fi

run chown -R "$DEPLOY_USER:$DEPLOY_USER" "$SSH_DIR"

if [ "$ADD_TO_DOCKER_GROUP" = "1" ]; then
  if getent group docker >/dev/null 2>&1; then
    run usermod -aG docker "$DEPLOY_USER"
  fi
fi

if [ "$SETUP_STACKS_DIR" = "1" ] && [ -d "$STACKS_DIR" ]; then
  run chown -R "$DEPLOY_USER:$DEPLOY_USER" "$STACKS_DIR"
  run find "$STACKS_DIR" -type d -exec chmod 755 {} \;
  run find "$STACKS_DIR" -type f -exec chmod 644 {} \;
fi

echo "DEPLOY_USER=$DEPLOY_USER"
echo "HOME_DIR=$HOME_DIR"
echo "SSH_DIR=$SSH_DIR"
echo "AUTHORIZED_KEYS=$AUTHORIZED_KEYS"
echo "KNOWN_HOSTS=$KNOWN_HOSTS"
EOS

log "Configurando usuário remoto: $DEPLOY_USER"
ssh -p "$PORT" \
  -o BatchMode=no \
  -o StrictHostKeyChecking=accept-new \
  "$REMOTE_TARGET" \
  "bash -s" -- \
  "$DEPLOY_USER" \
  "$PUB_KEY_B64" \
  "$REMOTE_USE_SUDO" \
  "$SETUP_STACKS_DIR" \
  "$STACKS_DIR" \
  "$ADD_TO_DOCKER_GROUP" \
  <<< "$REMOTE_SCRIPT"

log "Testando acesso com o usuário deploy"
ssh -p "$PORT" \
  -o BatchMode=yes \
  -o IdentitiesOnly=yes \
  -o StrictHostKeyChecking=accept-new \
  -i "$SSH_KEY" \
  "${DEPLOY_USER}@${HOST}" "echo 'Conexão OK com ${DEPLOY_USER}@${HOST}'"

cat <<EOF

==============================================
Configuração concluída com sucesso
==============================================

Alias SSH:
  ssh ${SSH_ALIAS}

Acesso direto:
  ssh -i ${SSH_KEY} -p ${PORT} ${DEPLOY_USER}@${HOST}

Chave privada:
  ${SSH_KEY}

Chave pública:
  ${SSH_PUB_KEY}

EOF

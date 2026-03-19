@formatter:off

@servers([
    'local' => '127.0.0.1',
    'app1' => 'deploy@195.200.0.186',
    'app2' => 'deploy_matrix_bnweb_org'
])


@groups
    'all'  => ['app1', 'demo', 'homo'],
    'test' => ['demo', 'homo'],
@endgroups


@setup
    /**
     * Parâmetros:
     * --path= (default '/srv/stacks/matrix')
     * --servers= (default 'app1')
     * --registry= (default 'ghcr.io')
     * --namespace= (default 'davidsonalencar')
     * --image= (default 'filament-app')
     * --tag= (default 'latest')
     * --schema= (default 'http')
     * --stack_repo= (default 'https://github.com/davidsonalencar/filament-stack.git')
     * --github_token= (default 'davidsonalencar')
     */

    $path = $path ?? '/srv/stacks/matrix';
    $servers = isset($servers) ? explode(',', $servers) : ['app1'];
    $registry = $registry ?? 'ghcr.io';
    $namespace = $namespace ?? 'davidsonalencar';
    $image = $image ?? 'filament-app';
    $tag = $tag ?? 'latest';
    $schema = $schema ?? 'http';
    $stack_repo = $stack_repo ?? 'https://github.com/davidsonalencar/filament-stack.git';
    $github_username = $github_username ?? 'davidsonalencar';
    $github_token = $github_token ?? env('GITHUB_TOKEN');

    $nginx_service = 'nginx';
    $app_services = 'app horizon scheduler reverb';

    $app_image = "$registry/$namespace/$image";
    $nginx_image = "$registry/$namespace/$image-nginx";

    $deploy_history = "$path/.deploy_history.txt"

@endsetup


@story('release', ['on' => 'local'])
    release_app
    publish_stack
    build_images
@endstory


@story('bootstrap', ['on' => $servers, 'parallel' => true])
    if [ -z "{{ $domain }}" ]; then
        echo ""
        echo "❌ Erro: parâmetro obrigatório ausente"
        echo " "
        echo "Uso correto:"
        echo "  envoy run deploy --domain=filament.davidsonalencar.com"
        echo " "
        exit 1
    fi

    clone_update_stack
    config_env
    docker_login
    pull_image
    up_services
@endstory


@story('deploy', ['on' => $servers, 'parallel' => true])
{{--conferir se existe versões novas, caso contrario não faz nada--}}
    save_current_tag_history
    clone_update_stack
    set_deploy_env_tag
    pull_image
    ensure_network
    start_deployment
    wait_deployment
    switch_to_deployment
    up_app
    switch_back_to_app
    remove_deployment
@endstory


@story('rollback', ['on' => $servers, 'parallel' => true])
    rollback_stack
    set_rollback_env_tag
    pull_image
    start_deployment
    wait_deployment
    switch_to_deployment
    up_app
    switch_back_to_app
    remove_deployment
    remove_previous_tag_history
    rollback_database
@endstory


{{--  --}}
{{--  --}}
{{--  --}}
{{--  --}}


@task('release_app', ['on' => 'local'])
    set -euo pipefail

    VERSION_OPTION={{ $tag }}

    echo "🔍 Buscando última tag..."

    LAST_TAG=$(git describe --tags --abbrev=0 --exclude latest --exclude "*alpha*" --exclude "*beta*" --exclude "*rc*" --exclude "*dev*" --exclude "*pre*" --exclude "*alfa*" 2>/dev/null || echo "")

    if [ -z "$LAST_TAG" ]; then
      echo "⚠️ Nenhuma tag encontrada. Usando todos os commits."
      COMMITS=$(git log --pretty=format:'%s')
    else
      echo "📌 Última tag: $LAST_TAG"
      COMMITS=$(git log "$LAST_TAG"..HEAD --pretty=format:'%s')
    fi

    if [ -z "$COMMITS" ]; then
      echo "⚠️ Sem commits novos."
      exit 1
    fi

    # -------------------------------
    # 🔢 BUMP DE VERSÃO
    # -------------------------------

    YEAR=$(date +%Y)
    MONTH=$(date +%-m)

    if [ "$VERSION_OPTION" != "latest" ]; then
      NEW_VERSION=$VERSION_OPTION
    else
      if [ -z "$LAST_TAG" ]; then
        NEW_VERSION="$YEAR.$MONTH.1"
      else
        # remove prefixo v
        CLEAN_TAG=${LAST_TAG#v}

        IFS='.' read -r VYEAR VMONTH VPATCH <<< "$CLEAN_TAG"

        if [ "$VYEAR" = "$YEAR" ] && [ "$VMONTH" = "$MONTH" ]; then
          NEW_VERSION="$YEAR.$MONTH.$((VPATCH + 1))"
        else
          NEW_VERSION="$YEAR.$MONTH.1"
        fi
      fi
    fi

    echo "🚀 Nova versão: $NEW_VERSION"

    # -------------------------------
    # 📄 ATUALIZA VERSION
    # -------------------------------

    echo "$NEW_VERSION" > VERSION

    # -------------------------------
    # 📝 CHANGELOG
    # -------------------------------

    if [[ ! "$NEW_VERSION" =~ (alpha|beta|rc|dev|pre|alfa) ]]; then
        DATE=$(date +%Y-%m-%d)

        CHANGELOG_CONTENT="## $NEW_VERSION - $DATE\n\n"

        while IFS= read -r line; do
          CHANGELOG_CONTENT="$CHANGELOG_CONTENT- $line\n"
        done <<< "$COMMITS"

        CHANGELOG_CONTENT="$CHANGELOG_CONTENT\n"

        if [ -f CHANGELOG.md ]; then
          echo "$CHANGELOG_CONTENT$(cat CHANGELOG.md)" > CHANGELOG.md
        else
          echo "$CHANGELOG_CONTENT" > CHANGELOG.md
        fi
    else
        echo "❌ Versão de pré-release detectada. Changelogs não alterado."
    fi

    # -------------------------------
    # 📦 COMMIT + TAG
    # -------------------------------

    git add VERSION CHANGELOG.md
    git commit -m "chore(release): $NEW_VERSION"
    git tag "$NEW_VERSION"

    if [[ ! "$NEW_VERSION" =~ (alpha|beta|rc|dev|pre|alfa) ]]; then
        git tag -a "latest" -m "Latest release -> ${NEW_VERSION}" -f
    fi

    # -------------------------------
    # 🚀 PUSH
    # -------------------------------

    git push origin HEAD
    git push --tags --force

    echo "✅ Release criada com sucesso!"

@endtask

@task('publish_stack', ['on' => 'local'])
    set -euo pipefail

    CURR_DIR=$(pwd)
    TEMP_DIR=$(mktemp -d)
    STACK_DIR="${TEMP_DIR}/stack"
    TAG="$(cat VERSION)"

    echo ">> Clonando repositório público {{ $stack_repo }}"
    git clone {{ $stack_repo }} "${STACK_DIR}"

    cd "${STACK_DIR}"
    git checkout main || git checkout -b main

    echo ">> Preparando arquivos do stack"
    mkdir -p .docker/scripts .docker/nginx

    cp "$CURR_DIR/.docker/nginx/app_upstream.conf" .docker/nginx/.
    cp -Rf "$CURR_DIR/.docker/compose/." .docker/compose/
    cp -Rf "$CURR_DIR/.env.prod.example" .
    cp -Rf "$CURR_DIR/compose.yaml" .

    echo ">> Configurando variáveis de ambiente"
    if grep -q "^APP_IMAGE=" .env.prod.example 2>/dev/null; then
        sed -i "s|^APP_IMAGE=.*|APP_IMAGE={{ $app_image }}|" .env.prod.example
    else
        echo "" >> .env.prod.example
        echo "APP_IMAGE={{ $app_image }}" >> .env.prod.example
    fi

    if grep -q "^APP_TAG=" .env.prod.example 2>/dev/null; then
        sed -i "s|^APP_TAG=.*|APP_TAG=${TAG}|" .env.prod.example
    else
        echo "APP_TAG=${TAG}" >> .env.prod.example
    fi

    if grep -q "^NGINX_IMAGE=" .env.prod.example 2>/dev/null; then
        sed -i "s|^NGINX_IMAGE=.*|NGINX_IMAGE={{ $nginx_image }}|" .env.prod.example
    else
        echo "NGINX_IMAGE={{ $nginx_image }}" >> .env.prod.example
    fi

    if grep -q "^NGINX_TAG=" .env.prod.example 2>/dev/null; then
        sed -i "s|^NGINX_TAG=.*|NGINX_TAG=${TAG}|" .env.prod.example
    else
        echo "NGINX_TAG=${TAG}" >> .env.prod.example
    fi

    echo ">> Adicionando arquivos ao git"
    git add .

    echo ">> Criando commit"
    git diff --cached --quiet || git commit -m "Update stack files for version ${TAG}"

    echo ">> Criando tag de versão ${TAG}"
    git tag -a "${TAG}" -m "Release version ${TAG}" -f

    echo ">> Enviando commits para o servidor"
    git push origin main

    echo ">> Enviando tag de versão: ${TAG}"
    git push origin "${TAG}" --force

    echo ">> Limpando diretório temporário"
    rm -rf "${TEMP_DIR}"

    echo ">> Stack publicado com sucesso"
@endtask


@task('build_images', ['on' => 'local'])
    set -euo pipefail

    TAG="$(cat VERSION)"
    APP_IMAGE="{{ $app_image }}"
    NGINX_IMAGE="{{ $nginx_image }}"

    LOWER_TAG=$(printf '%s' "$TAG" | tr '[:upper:]' '[:lower:]')

    PUBLISH_LATEST=1
    case "$LOWER_TAG" in
      *stable*|*beta*|*dev*|*alpha*|*latest*)
        PUBLISH_LATEST=0
        ;;
    esac

    # -------------------------
    # APP IMAGE
    # -------------------------
    echo ">> Building ${APP_IMAGE}:${TAG}"

    BUILD_TAGS="-t ${APP_IMAGE}:${TAG}"

    if [ "$PUBLISH_LATEST" -eq 1 ]; then
      echo ">> Também marcando como latest"
      BUILD_TAGS="$BUILD_TAGS -t ${APP_IMAGE}:latest"
    else
      echo ">> Tag contém stable, beta ou dev; não será marcado como latest"
    fi

    docker buildx build \
      --progress=quiet \
      --platform linux/amd64,linux/arm64 \
      --target production \
      --build-arg APP_VERSION=$TAG \
      $BUILD_TAGS \
      -f .docker/app/Dockerfile \
      --push .

    # -------------------------
    # NGINX IMAGE
    # -------------------------
    echo ">> Building ${NGINX_IMAGE}:${TAG}"

    BUILD_TAGS_NGINX="-t ${NGINX_IMAGE}:${TAG}"

    if [ "$PUBLISH_LATEST" -eq 1 ]; then
      BUILD_TAGS_NGINX="$BUILD_TAGS_NGINX -t ${NGINX_IMAGE}:latest"
    fi

    docker buildx build \
      --progress=quiet \
      --platform linux/amd64,linux/arm64 \
      --target production \
      $BUILD_TAGS_NGINX \
      -f .docker/nginx/Dockerfile \
      --push .
@endtask


{{--  --}}
{{--  --}}
{{--  --}}
{{--  --}}


@task('save_current_tag_history', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    echo ">> Salvando versão atual antes do deploy"

    if [ -f .env ]; then
        CURRENT_TAG=$(grep '^APP_TAG=' .env | cut -d'=' -f2)
        if [ -n "$CURRENT_TAG" ]; then
            echo "${CURRENT_TAG}" >> {{ $deploy_history }}
            echo ">> Versão atual adicionada ao histórico: ${CURRENT_TAG}"
        else
            echo ">> Nenhuma tag encontrada no .env"
        fi
    else
        echo ">> Arquivo .env não encontrado, primeira instalação"
    fi
@endtask


@task('clone_update_stack', ['on' => $servers, 'parallel' => true])
    set -euo pipefail

    echo ">> Verificando se o diretório {{ $path }} já existe"
    if [ -d "{{ $path }}" ]; then
        echo ">> Diretório já existe, atualizando repositório"
        cd {{ $path }}
    else
        echo ">> Clonando repositório {{ $stack_repo }} em {{ $path }}"
        git clone {{ $stack_repo }} {{ $path }}
        cd {{ $path }}
    fi

    git fetch origin --prune --tags --force
    git reset --hard {{ $tag }}

    echo ">> Repositório clonado/atualizado com sucesso (tag: {{ $tag }})"
@endtask


@task('ensure_network', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    docker network inspect matrix_network >/dev/null 2>&1 || docker network create matrix_network
@endtask


@task('config_env', ['on' => $servers, 'parallel' => true])
    set -euo pipefail

    cd {{ $path }}

    if [ ! -f .env ]; then
        echo ">> Criando arquivo .env a partir de .env.prod.example"
        cp .env.prod.example .env
    fi

    echo ">> Configurando WWWUSER e WWWGROUP"
    WWWUSER=$(id -u)
    WWWGROUP=$(id -g)

    if grep -q "^WWWUSER=" .env 2>/dev/null; then
        sed -i "s|^WWWUSER=.*|WWWUSER=${WWWUSER}|" .env
    else
        echo "" >> .env
        echo "WWWUSER=${WWWUSER}" >> .env
    fi

    if grep -q "^WWWGROUP=" .env 2>/dev/null; then
        sed -i "s|^WWWGROUP=.*|WWWGROUP=${WWWGROUP}|" .env
    else
        echo "WWWGROUP=${WWWGROUP}" >> .env
    fi

    echo ">> Configurando APP_HOST"
    if grep -q "^APP_HOST=" .env 2>/dev/null; then
        sed -i "s|^APP_HOST=.*|APP_HOST={{ $domain }}|" .env
    else
        echo "APP_HOST={{ $domain }}" >> .env
    fi

    echo ">> Configurando APP_SCHEMA"
    if grep -q "^APP_SCHEMA=" .env 2>/dev/null; then
        sed -i "s|^APP_SCHEMA=.*|APP_SCHEMA={{ $schema }}|" .env
    else
        echo "APP_SCHEMA={{ $schema }}" >> .env
    fi

    echo ">> Gerando DB_PASSWORD se necessário"
    if ! grep -q "^DB_PASSWORD=" .env 2>/dev/null || [ -z "$(grep '^DB_PASSWORD=' .env | cut -d'=' -f2)" ]; then
        DB_PASSWORD=$(openssl rand -hex 16)
        if grep -q "^DB_PASSWORD=" .env 2>/dev/null; then
            sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
        else
            echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
        fi
    fi

    echo ">> Arquivo .env configurado com sucesso"
@endtask


@task('docker_login', ['on' => $servers, 'parallel' => true])
    set -euo pipefail

    # Token apenas para leitura de packages para o docker

    echo ">> Autenticando no GitHub Container Registry"
    echo {{ $github_token }} | docker login {{ $registry }} -u {{ $github_username }} --password-stdin

    echo ">> Autenticação realizada com sucesso"
@endtask


@task('pull_image', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose pull
@endtask


@task('up_services', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose up -d
    docker ps
@endtask


@task('up_app', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose up -d {{ $app_services }}
@endtask


{{--  --}}
{{--  --}}
{{--  --}}
{{--  --}}


@task('set_deploy_env_tag', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    DEPLOY_TAG="{{ $tag }}"

    if [ "$DEPLOY_TAG" = "latest" ]; then
        echo ">> Tag 'latest' detectada, buscando última versão do git"
        DEPLOY_TAG=$(git describe --tags --abbrev=0 --exclude latest --exclude "*alpha*" --exclude "*beta*" --exclude "*rc*" --exclude "*dev*" --exclude "*pre*" --exclude "*alfa*" 2>/dev/null || echo "latest")
        echo ">> Usando tag: ${DEPLOY_TAG}"
    fi

    if grep -q "^APP_TAG=" .env 2>/dev/null; then
        sed -i "s|^APP_TAG=.*|APP_TAG=${DEPLOY_TAG}|" .env
    else
        echo "APP_TAG=${DEPLOY_TAG}" >> .env
    fi

    if grep -q "^NGINX_TAG=" .env 2>/dev/null; then
        sed -i "s|^NGINX_TAG=.*|NGINX_TAG=${DEPLOY_TAG}|" .env
    else
        echo "NGINX_TAG=${DEPLOY_TAG}" >> .env
    fi
@endtask


@task('start_deployment', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose --env-file .env -f ./.docker/compose/deploy.yaml up -d
@endtask


@task('wait_deployment', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    CONTAINER=$(docker compose --env-file .env -f ./.docker/compose/deploy.yaml ps -q app_deployment)

    for i in $(seq 1 40); do
      STATUS=$(docker inspect --format='{{"{{"}}if .State.Health}}{{"{{"}}.State.Health.Status}}{{"{{"}}else}}starting{{"{{"}}end}}' $CONTAINER 2>/dev/null || true)

      if [ "$STATUS" = "healthy" ]; then
        echo "app_deployment healthy"
        exit 0
      fi

      echo "waiting app_deployment... attempt $i/40 status=$STATUS"
      sleep 3
    done

    docker logs $CONTAINER || true
    exit 1
@endtask


@task('switch_to_deployment', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    echo "server app_deployment:9000;" > ./.docker/nginx/app_upstream.conf

    docker compose exec {{ $nginx_service }} nginx -t
    docker compose exec {{ $nginx_service }} nginx -s reload
@endtask


@task('switch_back_to_app', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    echo "server app:9000;" > ./.docker/nginx/app_upstream.conf

    docker compose exec {{ $nginx_service }} nginx -t
    docker compose exec {{ $nginx_service }} nginx -s reload
@endtask


@task('remove_deployment', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose --env-file .env -f ./.docker/compose/deploy.yaml stop app_deployment || true
    docker compose --env-file .env -f ./.docker/compose/deploy.yaml rm -f app_deployment || true
@endtask


{{--  --}}
{{--  --}}
{{--  --}}
{{--  --}}

parei aqui
@task('rollback_stack', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    if [ ! -f {{ $deploy_history }} ] || [ ! -s {{ $deploy_history }} ]; then
        echo ">> Erro: Nenhum histórico de deploy encontrado"
        exit 1
    fi

    ROLLBACK_TAG=$(tail -n 1 {{ $deploy_history }})

    echo ">> Fazendo rollback para tag: ${ROLLBACK_TAG}"
    git fetch origin --prune --tags --force
    git reset --hard "${ROLLBACK_TAG}"
@endtask


@task('set_rollback_env_tag', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    ROLLBACK_TAG=$(tail -n 1 {{ $deploy_history }})

    echo ">> Atualizando variáveis de ambiente"
    if grep -q "^APP_TAG=" .env 2>/dev/null; then
        sed -i "s|^APP_TAG=.*|APP_TAG=${ROLLBACK_TAG}|" .env
    fi

    if grep -q "^NGINX_TAG=" .env 2>/dev/null; then
        sed -i "s|^NGINX_TAG=.*|NGINX_TAG=${ROLLBACK_TAG}|" .env
    fi
@endtask

@task('remove_previous_tag_history', ['on' => $servers, 'parallel' => true])
    set -euo pipefail

    if [ -f {{ $deploy_history }} ]; then
        sed -i '$ d' {{ $deploy_history }}
        echo ">> Última entrada removida do histórico de deploy"
    else
        echo ">> Nenhum histórico de deploy encontrado"
    fi
@endtask


{{--  --}}
{{--  --}}
{{--  --}}
{{--  --}}


@task('rollback_database', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    echo ">> Executando rollback do banco de dados"
    docker compose exec -T app php artisan migrate:rollback --force
    docker compose exec -T app php artisan optimize:clear
    docker compose exec -T app php artisan optimize
    echo ">> Rollback do banco de dados concluído"
@endtask


@task('backup_database', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

{{--  comando para realizar o backup  --}}
@endtask


@task('restore_database', ['on' => $servers, 'parallel' => false])
    set -euo pipefail
    cd {{ $path }}

{{--  comando para realizar o restauro  --}}
@endtask



@formatter:on

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
    $app_service = 'app horizon scheduler reverb';

    $app_image = "$registry/$namespace/$image";
    $nginx_image = "$registry/$namespace/$image-nginx";

    $deploy_history = "$path/.deploy_history.txt";

    $nginx_upstreams = "/etc/nginx/conf.d/upstreams.conf";

    $i = $i ?? '';
@endsetup


@story('bootstrap', ['on' => $servers, 'parallel' => true])
    bootstrap:validate
    bootstrap:stack
    env:config
    services:pull
    services:up
@endstory


@story('deploy', ['on' => $servers, 'parallel' => true])
    deploy:validate
    deploy:stack
    history:save
    env:set-deploy-tag
    app:pull
    deploy:up
    deploy:wait
    nginx:use-deploy
    app:up
    app:wait
    nginx:use-app
    deploy:down
@endstory


@story('rollback', ['on' => $servers, 'parallel' => true])
    rollback:validate
    rollback:stack
    env:set-rollback-tag
    app:pull
    deploy:up
    deploy:wait
    nginx:use-deploy
    app:up
    app:wait
    nginx:use-app
    deploy:down
    history:pop
@endstory


{{--------------------------------------------------------------------------------------------------------------------}}
{{-- RELEASE  --}}
{{--------------------------------------------------------------------------------------------------------------------}}


@task('release:stack', ['on' => 'local'])
    <?php require __DIR__.'/resources/views/envoy/env.sh' ?>

    CURR_DIR=$(pwd)
    TEMP_DIR=$(mktemp -d)
    STACK_DIR="${TEMP_DIR}/stack"
    TAG="$(cat VERSION)"

    echo ">> Clonando repositório público {{ $stack_repo }}"
    git clone {{ $stack_repo }} "${STACK_DIR}"

    cd "${STACK_DIR}"
    git checkout main || git checkout -b main

    echo ">> Preparando arquivos do stack"
    mkdir -p .docker/scripts

    cp -Rf "$CURR_DIR/.docker/compose/." .docker/compose/
    cp -Rf "$CURR_DIR/.env.prod.example" .
    cp -Rf "$CURR_DIR/compose.yaml" .

    echo ">> Configurando variáveis de ambiente"
    set_env APP_IMAGE "{{ $app_image }}" .env.prod.example
    set_env APP_TAG "$TAG" .env.prod.example
    set_env NGINX_IMAGE "{{ $nginx_image }}" .env.prod.example
    set_env NGINX_TAG "$TAG" .env.prod.example

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


@task('release:images', ['on' => 'local'])
    <?php require __DIR__.'/resources/views/envoy/common.sh' ?>

    TAG="$(cat VERSION)"
    APP_IMAGE="{{ $app_image }}"
    NGINX_IMAGE="{{ $nginx_image }}"
    IS_PR=$(is_prerelease_tag "$TAG")

    # -------------------------
    # APP IMAGE
    # -------------------------
    echo ">> Building ${APP_IMAGE}:${TAG}"

    BUILD_TAGS="-t ${APP_IMAGE}:${TAG}"
    if [ "$IS_PR" -eq 1 ]; then
      BUILD_TAGS="$BUILD_TAGS -t ${APP_IMAGE}:latest"
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
    if [ "$IS_PR" -eq 1 ]; then
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


{{--------------------------------------------------------------------------------------------------------------------}}
{{-- BOOTSTRAP --}}
{{--------------------------------------------------------------------------------------------------------------------}}


@task('bootstrap:validate', ['on' => $servers, 'parallel' => true])
    <?php require __DIR__.'/resources/views/envoy/common.sh' ?>

    domain="{{ $domain }}"
    path="{{ $path }}"

    require_arg domain
    require_arg path
    require_file "{{ $path }}/.env"
@endtask


@task('bootstrap:stack', ['on' => $servers, 'parallel' => true])
    set -euo pipefail

    echo ">> Clonando repositório {{ $stack_repo }} em {{ $path }}"
    git clone {{ $stack_repo }} {{ $path }}
@endtask


{{--------------------------------------------------------------------------------------------------------------------}}
{{-- DEPLOY --}}
{{--------------------------------------------------------------------------------------------------------------------}}


@task('deploy:validate', ['on' => $servers, 'parallel' => true])
    <?php require __DIR__.'/resources/views/envoy/common.sh' ?>
    <?php require __DIR__.'/resources/views/envoy/env.sh' ?>
    <?php require __DIR__.'/resources/views/envoy/git.sh' ?>

    cd {{ $path }}

{{-- VERIFICAR A VERSAI EM USO NO CONTAINER APP --}}
    require_file "{{ $path }}/.env"

    log_info "Verificando se existe nova versão para deploy"

    docker network inspect matrix_network >/dev/null 2>&1 || {
      fail "Network matrix_network não existe"
    }

    DEPLOY_TAG=$(resolve_version_tag "{{ $tag }}")
    if [ -z "$DEPLOY_TAG" ]; then
        fail "--tag nao definida"
    fi

    CURRENT_TAG=$(get_env "APP_TAG")
    if [ -z "$CURRENT_TAG" ]; then
        log_warn "Nenhuma versão atual encontrada, prosseguindo com deploy"
        exit 0
    fi

    log_info "Versão atual: ${CURRENT_TAG}"
    log_info "Versão alvo: ${DEPLOY_TAG}"

    if [ "$CURRENT_TAG" = "$DEPLOY_TAG" ]; then
        fail "Versão já está atualizada, nenhuma ação necessária"
    fi

    log_info "Nova versão detectada, prosseguindo com deploy"
@endtask


@task('deploy:stack', ['on' => $servers, 'parallel' => true])
    <?php require __DIR__.'/resources/views/envoy/git.sh' ?>

    cd {{ $path }}

    DEPLOY_TAG=$(resolve_version_tag "{{ $tag  }}")

    git fetch origin --prune --tags --force
    git reset --hard $DEPLOY_TAG

    echo ">> Repositório clonado/atualizado com sucesso (tag: $DEPLOY_TAG)"
@endtask


@task('deploy:up', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose --env-file .env -f ./.docker/compose/deploy.yaml up -d
@endtask


@task('deploy:wait', ['on' => $servers, 'parallel' => true])
    <?php require __DIR__.'/resources/views/envoy/common.sh' ?>
    <?php require __DIR__.'/resources/views/envoy/docker.sh' ?>
    cd {{ $path }}

    CONTAINER=$(docker compose --env-file .env -f ./.docker/compose/deploy.yaml ps -q app_deployment)
    require_var CONTAINER
    wait_for_container_health $CONTAINER
@endtask


@task('deploy:down', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose --env-file .env -f ./.docker/compose/deploy.yaml stop app_deployment || true
    docker compose --env-file .env -f ./.docker/compose/deploy.yaml rm -f app_deployment || true
@endtask


{{--------------------------------------------------------------------------------------------------------------------}}
{{-- HISTORY --}}
{{--------------------------------------------------------------------------------------------------------------------}}


@task('history:save', ['on' => $servers, 'parallel' => true])
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


@task('history:pop', ['on' => $servers, 'parallel' => true])
    set -euo pipefail

    if [ -f {{ $deploy_history }} ]; then
        sed -i '$ d' {{ $deploy_history }}
        echo ">> Última entrada removida do histórico de deploy"
    else
        echo ">> Nenhum histórico de deploy encontrado"
    fi
@endtask


{{--------------------------------------------------------------------------------------------------------------------}}
{{-- ENV --}}
{{--------------------------------------------------------------------------------------------------------------------}}

@task('env:config', ['on' => $servers, 'parallel' => true])
    <?php require __DIR__.'/resources/views/envoy/env.sh' ?>

    cd {{ $path }}

    echo ">> Criando arquivo .env a partir de .env.prod.example"
    cp .env.prod.example .env

    echo ">> Configurando WWWUSER e WWWGROUP"
    set_env WWWUSER "$(id -u)"
    set_env WWWGROUP "$(id -g)"

    echo ">> Configurando APP_HOST"
    set_env APP_HOST "{{ $domain }}"

    echo ">> Configurando APP_SCHEMA"
    set_env APP_SCHEMA "{{ $schema }}"

    echo ">> Gerando DB_PASSWORD se necessário"
    set_env DB_PASSWORD "$(openssl rand -hex 16)"

    echo ">> Arquivo .env configurado com sucesso"
@endtask


@task('env:set-deploy-tag', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    <?php require __DIR__.'/resources/views/envoy/env.sh' ?>
    cd {{ $path }}

    DEPLOY_TAG="{{ $tag }}"
    if [ "$DEPLOY_TAG" = "latest" ]; then
        DEPLOY_TAG=$(git tag | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -n 1)
    fi

    set_env APP_TAG "${DEPLOY_TAG}"
    set_env NGINX_TAG "${DEPLOY_TAG}"
@endtask


@task('env:set-rollback-tag', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    <?php require __DIR__.'/resources/views/envoy/env.sh' ?>

    cd {{ $path }}

    ROLLBACK_TAG=$(tail -n 1 {{ $deploy_history }})

    set_env APP_TAG "${ROLLBACK_TAG}"
    set_env NGINX_TAG "${ROLLBACK_TAG}"
@endtask


{{--------------------------------------------------------------------------------------------------------------------}}
{{-- IMAGE --}}
{{--------------------------------------------------------------------------------------------------------------------}}


@task('services:pull', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose pull
@endtask


@task('services:up', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose up -d
    docker ps
@endtask


@task('app:up', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose up -d --no-deps {{ $app_service }}
@endtask


@task('app:pull', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}
    docker compose pull app
@endtask

@task('app:wait', ['on' => $servers, 'parallel' => true])
    <?php require __DIR__.'/resources/views/envoy/docker.sh' ?>

    cd {{ $path }}

    CONTAINER=$(docker compose ps -q app)
    wait_for_container_health $CONTAINER
@endtask


{{--------------------------------------------------------------------------------------------------------------------}}
{{-- NGINX --}}
{{--------------------------------------------------------------------------------------------------------------------}}

w
@task('nginx:use-deploy', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    docker compose exec -T {{ $nginx_service }} sh
    sed -E -i "s| app:| app_deployment:|" "{{ $nginx_upstreams }}"
    nginx -t
    nginx -s reload
    exit
@endtask


@task('nginx:use-app', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    docker compose exec -T {{ $nginx_service }} sh
    sed -E -i "s| app_deployment:| app:|" "{{ $nginx_upstreams }}"
    nginx -t
    nginx -s reload
    exit
@endtask


{{--------------------------------------------------------------------------------------------------------------------}}
{{-- ROLLBACK --}}
{{--------------------------------------------------------------------------------------------------------------------}}


@task('rollback:validate', ['on' => $servers, 'parallel' => true])
    <?php require __DIR__.'/resources/views/envoy/common.sh' ?>

    cd {{ $path }}

    require_file "{{ $deploy_history }}"

    ROLLBACK_TAG=$(tail -n 1 {{ $deploy_history }})
    if [ -z "$ROLLBACK_TAG" ]; then
        fail "Nenhum histórico de deploy encontrado"
    fi
@endtask


@task('rollback:stack', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    ROLLBACK_TAG=$(tail -n 1 {{ $deploy_history }})

    echo ">> Fazendo rollback para tag: ${ROLLBACK_TAG}"
    git fetch origin --prune --tags --force
    git reset --hard "${ROLLBACK_TAG}"
@endtask


{{--------------------------------------------------------------------------------------------------------------------}}
{{-- DB --}}
{{--------------------------------------------------------------------------------------------------------------------}}


@task('db:rollback', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

    echo ">> Executando rollback do banco de dados"
    docker compose exec -T app php artisan migrate:rollback --force
    docker compose exec -T app php artisan optimize:clear
    docker compose exec -T app php artisan optimize
    echo ">> Rollback do banco de dados concluído"
@endtask


@task('db:backup', ['on' => $servers, 'parallel' => true])
    set -euo pipefail
    cd {{ $path }}

{{--  comando para realizar o backup  --}}
@endtask


@task('db:restore', ['on' => $servers, 'parallel' => false])
    set -euo pipefail
    cd {{ $path }}

{{--  comando para realizar o restauro  --}}
@endtask


@formatter:on

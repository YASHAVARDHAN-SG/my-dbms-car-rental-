pipeline {
    agent {
        label 'jenkins-slave-1 && !built-in && !master'
    }

    options {
        skipDefaultCheckout(true)
        timestamps()
        disableConcurrentBuilds()
    }

    triggers {
        githubPush()
    }

    parameters {
        string(
            name: 'WEBSITE_PORT',
            defaultValue: '8080',
            description: 'Available TCP port on jenkins-slave-1 for the website'
        )
    }

    environment {
        SONARQUBE_IMAGE = 'sonarqube:lts-community'
        SONAR_SCANNER_IMAGE = 'sonarsource/sonar-scanner-cli:latest'
        CURL_IMAGE = 'curlimages/curl:8.12.1'
        PACKAGER_IMAGE = 'alpine:3.21'
        MARIADB_IMAGE = 'mariadb:11.4'
    }

    stages {
        stage('Pipeline Started') {
            steps {
                slackSend(
                    channel: '#jenkins-jobs-car-rental',
                    color: '#439FE0',
                    message: """🏁 *PIPELINE STARTED*

🚀 *Project:* Car Rental
🛠️ *Job:* ${env.JOB_NAME}
🔢 *Build:* #${env.BUILD_NUMBER}
⏳ *Status:* IN PROGRESS
🔗 *Track Progress:* ${env.BUILD_URL}

⚙️ The CI/CD pipeline has started. Stay tuned for updates!""",
                    failOnError: false
                )
            }
        }

        stage('Checkout') {
            steps {
                script {
                    echo "Running pipeline on Jenkins node: ${env.NODE_NAME}"
                    if (env.NODE_NAME == 'built-in' || env.NODE_NAME == 'master') {
                        error('Pipeline must run on jenkins-slave-1, not the built-in node.')
                    }
                }
                sh '''
                    set -eu
                    docker run --rm \
                        --volume "$WORKSPACE:/workspace" \
                        --workdir /workspace \
                        --entrypoint sh \
                        "$PACKAGER_IMAGE" \
                        -c 'find /workspace -mindepth 1 -maxdepth 1 -exec rm -rf {} \\;'
                '''
                checkout scm
            }
        }

        stage('Start SonarQube') {
            steps {
                sh '''
                    set -eu
                    NETWORK=car-rental-sonarqube
                    CONTAINER=car-rental-sonarqube
                    docker info >/dev/null
                    docker network inspect "$NETWORK" >/dev/null 2>&1 || docker network create "$NETWORK"
                    docker volume create car-rental-sonarqube-data >/dev/null
                    docker volume create car-rental-sonarqube-extensions >/dev/null
                    docker volume create car-rental-sonarqube-logs >/dev/null

                    if ! docker container inspect "$CONTAINER" >/dev/null 2>&1; then
                        docker container run --detach \
                            --name "$CONTAINER" \
                            --restart unless-stopped \
                            --network "$NETWORK" \
                            --network-alias sonarqube \
                            --publish 9000:9000 \
                            --volume car-rental-sonarqube-data:/opt/sonarqube/data \
                            --volume car-rental-sonarqube-extensions:/opt/sonarqube/extensions \
                            --volume car-rental-sonarqube-logs:/opt/sonarqube/logs \
                            --ulimit nofile=131072:131072 \
                            --ulimit nproc=8192:8192 \
                            "$SONARQUBE_IMAGE"
                    elif [ "$(docker container inspect --format '{{.State.Running}}' "$CONTAINER")" != true ]; then
                        docker container start "$CONTAINER"
                    fi

                    attempt=0
                    while [ "$attempt" -lt 120 ]; do
                        if docker run --rm --network "$NETWORK" "$CURL_IMAGE" \
                            --fail --silent --show-error http://sonarqube:9000/api/system/status 2>/dev/null \
                            | grep -Eq '"status"[[:space:]]*:[[:space:]]*"UP"'; then
                            echo "SonarQube UI: http://<jenkins-slave-1-host>:9000"
                            exit 0
                        fi
                        attempt=$((attempt + 1))
                        sleep 5
                    done

                    docker container logs "$CONTAINER"
                    docker container inspect --format \
                        'OOMKilled={{.State.OOMKilled}} ExitCode={{.State.ExitCode}}' \
                        "$CONTAINER" || true
                    docker info --format \
                        'Docker host memory bytes: {{.MemTotal}}' || true
                    echo "SonarQube did not become ready within 10 minutes." >&2
                    echo "Exit code 137 usually means the Docker host or its VM ran out of memory and killed Elasticsearch." >&2
                    exit 1
                '''
            }
        }

        stage('PHP Unit Tests and Coverage') {
            steps {
                sh '''
                    set -eu
                    TEST_IMAGE="car-rental-php-tests:${BUILD_NUMBER}"

                    docker build \
                        --file Dockerfile.test \
                        --tag "$TEST_IMAGE" \
                        .

                    docker run --rm \
                        --volume "$WORKSPACE:/workspace" \
                        --workdir /workspace \
                        --user "$(id -u):$(id -g)" \
                        --env XDEBUG_MODE=coverage \
                        "$TEST_IMAGE" \
                        --configuration /workspace/phpunit.xml

                    test -s coverage.xml
                    echo "Coverage report generated:"
                    ls -l coverage.xml
                '''
            }
        }

        stage('SonarQube Analysis') {
            steps {
                withCredentials([string(credentialsId: 'sonarqube-token', variable: 'SONAR_TOKEN')]) {
                    sh '''
                        set -eu
                        docker run --rm \
                            --network car-rental-sonarqube \
                            --volume "$WORKSPACE:/usr/src" \
                            --workdir /usr/src \
                            --user "$(id -u):$(id -g)" \
                            --env SONAR_USER_HOME=/tmp/sonar \
                            --env SONAR_TOKEN \
                            "$SONAR_SCANNER_IMAGE" \
                            -Dsonar.host.url=http://sonarqube:9000 \
                            -Dsonar.qualitygate.wait=true
                    '''
                }
            }
        }
        stage('Build Artifact') {
            steps {
                sh '''
                    set -eu
                    docker run --rm \
                        --volume "$WORKSPACE:/workspace" \
                        --workdir /workspace \
                        --user "$(id -u):$(id -g)" \
                        --env BUILD_NUMBER \
                        "$PACKAGER_IMAGE" \
                        sh -c '
                            mkdir -p dist
                            tar \
                                --exclude=./.git \
                                --exclude=./dist \
                                --exclude=./reports \
                                --exclude=./.scannerwork \
                                -czf "dist/car-rental-portal-${BUILD_NUMBER}.tar.gz" \
                                .
                        '
                '''
                archiveArtifacts artifacts: 'dist/car-rental-portal-*.tar.gz', fingerprint: true
            }
        }

        stage('Deploy to Docker') {
            steps {
                withCredentials([
                    string(credentialsId: 'car-rental-db-root-password', variable: 'DB_ROOT_PASSWORD'),
                    string(credentialsId: 'car-rental-db-password', variable: 'DB_PASSWORD')
                ]) {
                    sh '''
                    set -eu
                    WEBSITE_PORT=${WEBSITE_PORT:-8080}
                    case "$WEBSITE_PORT" in
                        ''|*[!0-9]*)
                            echo "WEBSITE_PORT must be a port number from 1 to 65535." >&2
                            exit 1
                            ;;
                    esac
                    if [ "$WEBSITE_PORT" -lt 1 ] || [ "$WEBSITE_PORT" -gt 65535 ]; then
                        echo "WEBSITE_PORT must be a port number from 1 to 65535." >&2
                        exit 1
                    fi

                    docker network inspect car-rental-app >/dev/null 2>&1 || docker network create car-rental-app
                    docker volume create car-rental-db-data >/dev/null
                    DB_CONTAINER=car-rental-db
                    if ! docker container inspect "$DB_CONTAINER" >/dev/null 2>&1; then
                        docker run --detach \
                            --name "$DB_CONTAINER" \
                            --restart unless-stopped \
                            --network car-rental-app \
                            --network-alias car-rental-db \
                            --env MARIADB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" \
                            --env MARIADB_DATABASE=carrental \
                            --env MARIADB_USER=car_rental_app \
                            --env MARIADB_PASSWORD="$DB_PASSWORD" \
                            --health-cmd='healthcheck.sh --connect --innodb_initialized' \
                            --health-interval=10s \
                            --health-timeout=5s \
                            --health-retries=10 \
                            --volume car-rental-db-data:/var/lib/mysql \
                            --volume "$WORKSPACE/carrental.sql:/docker-entrypoint-initdb.d/001-carrental.sql:ro" \
                            "$MARIADB_IMAGE"
                    elif [ "$(docker container inspect --format '{{.State.Running}}' "$DB_CONTAINER")" != true ]; then
                        docker container start "$DB_CONTAINER"
                    fi

                    DB_NETWORKS=$(docker container inspect --format '{{json .NetworkSettings.Networks}}' "$DB_CONTAINER")
                    if ! printf '%s' "$DB_NETWORKS" | grep -q '"car-rental-app"'; then
                        docker network connect car-rental-app "$DB_CONTAINER"
                    fi

                    attempt=0
                    while [ "$attempt" -lt 60 ]; do
                        if TABLE_COUNT=$(docker run --rm \
                            --network car-rental-app \
                            --env MYSQL_PWD="$DB_PASSWORD" \
                            "$MARIADB_IMAGE" \
                            mariadb \
                                --connect-timeout=5 \
                                --host=car-rental-db \
                                --user=car_rental_app \
                                --database=carrental \
                                --batch \
                                --skip-column-names \
                                --execute="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('tblusers', 'tblbrands', 'tblvehicles', 'tblbooking')" \
                            2>/dev/null); then
                            if [ "$TABLE_COUNT" != 4 ]; then
                                echo "The persistent MariaDB database is missing required application tables." >&2
                                echo "Back up and migrate the existing database before deploying; the pipeline will not replace or reset it." >&2
                                exit 1
                            fi
                            break
                        fi
                        attempt=$((attempt + 1))
                        sleep 5
                    done
                    if [ "$attempt" -ge 60 ]; then
                        echo "MariaDB did not become ready with the configured application credentials." >&2
                        echo "For an existing data volume, verify the Jenkins DB credentials and perform any required schema migration manually." >&2
                        docker logs --tail 80 "$DB_CONTAINER" || true
                        exit 1
                    fi

                    IMAGE=car-rental-portal:latest
                    CANDIDATE="car-rental-portal-candidate-${BUILD_NUMBER}"
                    ROLLBACK_IMAGE="car-rental-portal:rollback-${BUILD_NUMBER}"
                    OLD_IMAGE_REFS=$(docker image ls --format '{{.Repository}}:{{.Tag}}' car-rental-portal | sort -u)
                    OLD_CONTAINER_IMAGE=

                    mkdir -p /home/ubuntu/docker_images
                    for OLD_IMAGE_REF in $OLD_IMAGE_REFS; do
                        SAFE_TAG=$(printf '%s' "$OLD_IMAGE_REF" | tr ':/' '--')
                        docker image save \
                            --output "/home/ubuntu/docker_images/${SAFE_TAG}-before-build-${BUILD_NUMBER}.tar" \
                            "$OLD_IMAGE_REF"
                    done

                    if docker container inspect car-rental-portal >/dev/null 2>&1; then
                        OLD_CONTAINER_IMAGE=$(docker container inspect --format '{{.Image}}' car-rental-portal)
                        docker image tag "$OLD_CONTAINER_IMAGE" "$ROLLBACK_IMAGE"
                        docker image save \
                            --output "/home/ubuntu/docker_images/car-rental-portal-running-before-build-${BUILD_NUMBER}.tar" \
                            "$ROLLBACK_IMAGE"
                    fi

                    if ! docker build --pull --tag "$IMAGE" .; then
                        docker image rm "$ROLLBACK_IMAGE" >/dev/null 2>&1 || true
                        exit 1
                    fi
                    NEW_IMAGE_ID=$(docker image inspect --format '{{.Id}}' "$IMAGE")

                    docker rm --force car-rental-portal >/dev/null 2>&1 || true
                    if ! docker run --detach \
                        --name "$CANDIDATE" \
                        --restart unless-stopped \
                        --network car-rental-app \
                        --publish "$WEBSITE_PORT:80" \
                        --env DB_HOST=car-rental-db \
                        --env DB_NAME=carrental \
                        --env DB_USER=car_rental_app \
                        --env DB_PASSWORD \
                        "$IMAGE"; then
                        docker rm --force "$CANDIDATE" >/dev/null 2>&1 || true
                        if [ -n "$OLD_CONTAINER_IMAGE" ]; then
                            docker image tag "$ROLLBACK_IMAGE" "$IMAGE"
                            docker run --detach \
                                --name car-rental-portal \
                                --restart unless-stopped \
                                --network car-rental-app \
                                --publish "$WEBSITE_PORT:80" \
                                --env DB_HOST=car-rental-db \
                                --env DB_NAME=carrental \
                                --env DB_USER=car_rental_app \
                                --env DB_PASSWORD \
                                "$ROLLBACK_IMAGE" || true
                        fi
                        docker image rm "$ROLLBACK_IMAGE" >/dev/null 2>&1 || true
                        if [ -n "$OLD_CONTAINER_IMAGE" ] && [ "$OLD_CONTAINER_IMAGE" != "$NEW_IMAGE_ID" ]; then
                            docker image rm "$NEW_IMAGE_ID" >/dev/null 2>&1 || true
                        fi
                        echo "Could not start the website on host port $WEBSITE_PORT. Choose an unused port and retry." >&2
                        exit 1
                    fi

                    attempt=0
                    while [ "$attempt" -lt 30 ]; do
                        if HTTP_STATUS=$(docker run --rm --network host "$CURL_IMAGE" \
                            --silent --show-error --output /dev/null --write-out '%{http_code}' \
                            "http://127.0.0.1:$WEBSITE_PORT/"); then
                            case "$HTTP_STATUS" in
                                2??)
                                    docker rename "$CANDIDATE" car-rental-portal
                                    docker image rm "$ROLLBACK_IMAGE" >/dev/null 2>&1 || true
                                    for OLD_IMAGE_REF in $OLD_IMAGE_REFS; do
                                        if [ "$OLD_IMAGE_REF" != "$IMAGE" ] && ! docker image rm "$OLD_IMAGE_REF"; then
                                            echo "Warning: unable to remove previous image $OLD_IMAGE_REF." >&2
                                        fi
                                    done
                                    if [ -n "$OLD_CONTAINER_IMAGE" ] && [ "$OLD_CONTAINER_IMAGE" != "$NEW_IMAGE_ID" ]; then
                                        if ! docker image rm "$OLD_CONTAINER_IMAGE"; then
                                            echo "Warning: unable to remove previous image ID $OLD_CONTAINER_IMAGE." >&2
                                        fi
                                    fi
                                    echo "Website is responding at http://<jenkins-slave-1-host>:$WEBSITE_PORT/"
                                    exit 0
                                    ;;
                            esac
                        fi
                        attempt=$((attempt + 1))
                        sleep 2
                    done

                    docker logs "$CANDIDATE"
                    docker rm --force "$CANDIDATE" >/dev/null 2>&1 || true
                    if [ -n "$OLD_CONTAINER_IMAGE" ]; then
                        docker image tag "$ROLLBACK_IMAGE" "$IMAGE"
                        if ! docker run --detach \
                            --name car-rental-portal \
                            --restart unless-stopped \
                            --network car-rental-app \
                            --publish "$WEBSITE_PORT:80" \
                            --env DB_HOST=car-rental-db \
                            --env DB_NAME=carrental \
                            --env DB_USER=car_rental_app \
                            --env DB_PASSWORD \
                            "$ROLLBACK_IMAGE"; then
                            echo "Warning: rollback container could not be started." >&2
                        fi
                    fi
                    docker image rm "$ROLLBACK_IMAGE" >/dev/null 2>&1 || true
                    if [ -n "$OLD_CONTAINER_IMAGE" ] && [ "$OLD_CONTAINER_IMAGE" != "$NEW_IMAGE_ID" ]; then
                        docker image rm "$NEW_IMAGE_ID" >/dev/null 2>&1 || true
                    fi
                    echo "The deployment candidate did not respond on host port $WEBSITE_PORT; rollback was attempted." >&2
                    exit 1
                    '''
                }
            }
        }
    }

    post {
        always {
            script {
                def rollbackImage = "car-rental-portal:rollback-${env.BUILD_NUMBER}"
                if (sh(returnStatus: true, script: "docker image inspect '${rollbackImage}' >/dev/null 2>&1") == 0) {
                    withCredentials([
                        string(credentialsId: 'car-rental-db-password', variable: 'DB_PASSWORD')
                    ]) {
                        sh '''
                set -eu
                WEBSITE_PORT=${WEBSITE_PORT:-8080}
                CANDIDATE="car-rental-portal-candidate-${BUILD_NUMBER}"
                ROLLBACK_IMAGE="car-rental-portal:rollback-${BUILD_NUMBER}"
                if docker image inspect "$ROLLBACK_IMAGE" >/dev/null 2>&1; then
                    if docker container inspect "$CANDIDATE" >/dev/null 2>&1; then
                        docker rm --force "$CANDIDATE" >/dev/null 2>&1 || true
                        docker rm --force car-rental-portal >/dev/null 2>&1 || true
                        docker image tag "$ROLLBACK_IMAGE" car-rental-portal:latest
                        if ! docker run --detach \
                            --name car-rental-portal \
                            --restart unless-stopped \
                            --network car-rental-app \
                            --publish "$WEBSITE_PORT:80" \
                            --env DB_HOST=car-rental-db \
                            --env DB_NAME=carrental \
                            --env DB_USER=car_rental_app \
                            --env DB_PASSWORD \
                            "$ROLLBACK_IMAGE"; then
                            echo "Warning: automatic website rollback could not start; previous image archive is in /var/tmp/old_docker_images." >&2
                        fi
                    elif ! docker container inspect car-rental-portal >/dev/null 2>&1; then
                        docker image tag "$ROLLBACK_IMAGE" car-rental-portal:latest
                        if ! docker run --detach \
                            --name car-rental-portal \
                            --restart unless-stopped \
                            --network car-rental-app \
                            --publish "$WEBSITE_PORT:80" \
                            --env DB_HOST=car-rental-db \
                            --env DB_NAME=carrental \
                            --env DB_USER=car_rental_app \
                            --env DB_PASSWORD \
                            "$ROLLBACK_IMAGE"; then
                            echo "Warning: automatic website rollback could not start; previous image archive is in /var/tmp/old_docker_images." >&2
                        fi
                    else
                        CONTAINER_IMAGE=$(docker container inspect --format '{{.Image}}' car-rental-portal)
                        LATEST_IMAGE=$(docker image inspect --format '{{.Id}}' car-rental-portal:latest)
                        if [ "$CONTAINER_IMAGE" != "$LATEST_IMAGE" ]; then
                            docker image tag "$ROLLBACK_IMAGE" car-rental-portal:latest
                        fi
                    fi
                    docker image rm "$ROLLBACK_IMAGE" >/dev/null 2>&1 || true
                fi
                '''
                    }
                } else {
                    echo 'No rollback image was created; skipping rollback cleanup.'
                }
            }
        }

        success {
            slackSend(
                channel: '#jenkins-jobs-car-rental',
                color: 'good',
                message: """🎉 *BUILD SUCCESSFUL*

🚀 *Project:* Car Rental
🛠️ *Job:* ${env.JOB_NAME}
🔢 *Build Number:* #${env.BUILD_NUMBER}
⏱️ *Status:* SUCCESS
🔗 *Build Details:* ${env.BUILD_URL}

✅ Build completed successfully !!!""",
                failOnError: false
            )
        }

        failure {
            slackSend(
                channel: '#jenkins-jobs-car-rental',
                color: 'danger',
                message: """🚨 *BUILD FAILED*

🚗 *Project:* Car Rental
🛠️ *Job:* ${env.JOB_NAME}
🔢 *Build Number:* #${env.BUILD_NUMBER}
❌ *Status:* FAILURE
🔗 *Console Output:* ${env.BUILD_URL}console

⚠️ Please investigate the failure and check the Jenkins console logs.""",
                failOnError: false
            )
        }
    }
}

pipeline {
    agent {
        label 'jenkins-slave-1 && !built-in && !master'
    }

    options {
        skipDefaultCheckout(true)
        timestamps()
    }

    triggers {
        githubPush()
    }

    environment {
        SONARQUBE_IMAGE = 'sonarqube:lts-community'
        SONAR_SCANNER_IMAGE = 'sonarsource/sonar-scanner-cli:latest'
        CURL_IMAGE = 'curlimages/curl:8.12.1'
        PACKAGER_IMAGE = 'alpine:3.21'
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

                    if ! docker inspect "$CONTAINER" >/dev/null 2>&1; then
                        docker run --detach \
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
                    elif [ "$(docker inspect --format '{{.State.Running}}' "$CONTAINER")" != true ]; then
                        docker start "$CONTAINER"
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

                    docker logs "$CONTAINER"
                    docker inspect --format \
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
                sh '''
                    set -eu
                    IMAGE="car-rental-portal:${BUILD_NUMBER}"

                    docker build --pull --tag "$IMAGE" .
                    docker rm --force car-rental-portal >/dev/null 2>&1 || true
                    docker run --detach \
                        --name car-rental-portal \
                        --restart unless-stopped \
                        --publish 80:80 \
                        "$IMAGE"

                    attempt=0
                    while [ "$attempt" -lt 30 ]; do
                        if docker run --rm --network host "$CURL_IMAGE" \
                            --fail --silent --show-error \
                            http://127.0.0.1/ >/dev/null; then
                            echo "Website is responding at http://<jenkins-slave-1-host>/"
                            exit 0
                        fi
                        attempt=$((attempt + 1))
                        sleep 2
                    done

                    docker logs car-rental-portal
                    echo "The deployed website did not respond on port 80." >&2
                    exit 1
                '''
            }
        }
    }

    post {
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

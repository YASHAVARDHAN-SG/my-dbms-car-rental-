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
                deleteDir()
                checkout scm
            }
        }

        stage('Start SonarQube') {
            steps {
                sh '''
                    set -eu
                    NETWORK="car-rental-$(printf '%s' "$BUILD_TAG" | tr -c 'A-Za-z0-9_.-' '-')"
                    CONTAINER="${NETWORK}-sonarqube"

                    docker info >/dev/null
                    docker network create "$NETWORK"
                    docker run --detach \
                        --name "$CONTAINER" \
                        --network "$NETWORK" \
                        --network-alias sonarqube \
                        --ulimit nofile=131072:131072 \
                        --ulimit nproc=8192:8192 \
                        "$SONARQUBE_IMAGE"

                    attempt=0
                    while [ "$attempt" -lt 120 ]; do
                        if docker run --rm --network "$NETWORK" "$CURL_IMAGE" \
                            --fail --silent --show-error \
                            http://sonarqube:9000/api/system/status 2>/dev/null \
                            | grep -Eq '"status"[[:space:]]*:[[:space:]]*"UP"'; then
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
                sh '''
                            set -eu
                            NETWORK="car-rental-$(printf '%s' "$BUILD_TAG" | tr -c 'A-Za-z0-9_.-' '-')"
                            ADMIN_PASSWORD="JenkinsSonar-${BUILD_NUMBER}-OnlyForThisBuild!"

                            docker run --rm --network "$NETWORK" "$CURL_IMAGE" \
                                --fail --silent --show-error --user admin:admin \
                                --data-urlencode login=admin \
                                --data-urlencode previousPassword=admin \
                                --data-urlencode "password=$ADMIN_PASSWORD" \
                                http://sonarqube:9000/api/users/change_password >/dev/null

                            TOKEN_RESPONSE=$(docker run --rm --network "$NETWORK" "$CURL_IMAGE" \
                                --fail --silent --show-error --user "admin:$ADMIN_PASSWORD" \
                                --data-urlencode "name=jenkins-${BUILD_NUMBER}" \
                                http://sonarqube:9000/api/user_tokens/generate)
                            SONAR_TOKEN=$(printf '%s' "$TOKEN_RESPONSE" \
                                | sed -n 's/.*"token":"\\([^"]*\\)".*/\\1/p')
                            test -n "$SONAR_TOKEN"
                            export SONAR_TOKEN

                            docker run --rm \
                                --network "$NETWORK" \
                                --volume "$WORKSPACE:/usr/src" \
                                --workdir /usr/src \
                                --env SONAR_TOKEN \
                                "$SONAR_SCANNER_IMAGE" \
                                -Dsonar.host.url=http://sonarqube:9000 \
                                -Dsonar.qualitygate.wait=true
                '''
            }
        }

        stage('Build Artifact') {
            steps {
                sh '''
                    set -eu
                    docker run --rm \
                        --volume "$WORKSPACE:/workspace" \
                        --workdir /workspace \
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
    }

    post {
        always {
            sh '''
                NETWORK="car-rental-$(printf '%s' "$BUILD_TAG" | tr -c 'A-Za-z0-9_.-' '-')"
                docker rm --force "${NETWORK}-sonarqube" >/dev/null 2>&1 || true
                docker network rm "$NETWORK" >/dev/null 2>&1 || true
            '''
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

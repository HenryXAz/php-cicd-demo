pipeline {

    agent any

    environment {
        APP_DIR = '/var/www/myapp'
        DEPLOY_HOST = 'production'
    }

    stages {

        stage('Checkout') {
            steps {
                echo "Código obtenido desde Git"
                sh '''
                    echo "Commit:"
                    git rev-parse --short HEAD

                    echo "Archivos:"
                    ls -la
                '''
            }
        }

        stage ('Install Dependencies') {
            steps {
                echo 'Instalando dependencias...'

                sh '''
                    composer install \
                    --no-interaction \
                    --prefer-dist
                '''
            }
        }

        stage('Validate') {
            steps {
                echo 'Validando sintaxis PHP...'

                sh '''
                    find . -name "*.php" \
                        -not -path "./vendor/*" \
                        -exec php -l {} \\;
                '''
            }
        }

        stage ('Unit Tests') {
            steps {
                echo 'Ejecutando PHPUnit...'

                sh '''
                    composer test tests
                '''
            }
        }

        stage ('Package') {
            echo 'Preparando release...'

            sh '''
                rm -rf build
                mkdir build

                cp -r public build/
                cp -r src build/
                cp -r vendor build/

                cp composer.json build/
                cp composer.lock build/

                echo "Contenido del artefacto:"
                find build -maxdepth 2 -type f | head -50
            '''
        }

        stage('Deploy') {
            steps {
                echo 'Desplegando release...'

                sh '''
                    RELEASE="release-${BUILD_NUMBER}"

                    ssh deploy@${DEPLOY_HOST} \
                        "readlink -f ${APP_DIR}/current || true" \
                        > previous_release.txt

                    ssh deploy@${DEPLOY_HOST} \
                        "mkdir -p ${APP_DIR}/releases/$RELEASE"

                    scp -r build/* \
                        deploy@${DEPLOY_HOST}:${APP_DIR}/releases/$RELEASE/

                    ssh deploy@${DEPLOY_HOST} \
                        "ln -sfn ${APP_DIR}/releases/$RELEASE ${APP_DIR}/current"

                    touch deployment_performed
                '''
            }
        }

        stage('Health Check') {
            steps {
                sh '''
                    sleep 2

                    curl --fail \
                         --silent \
                         --show-error \
                         http://${DEPLOY_HOST}/

                    echo
                    echo "Health check exitoso"
                '''
            }
        }
    }

    post {

        success {
            echo 'Pipeline completado correctamente.'
        }

        failure {
            echo 'Pipeline fallido.'

            sh '''
                if [ -f deployment_performed ] && \
                   [ -s previous_release.txt ]; then

                    PREVIOUS_RELEASE=$(cat previous_release.txt)

                    echo "Deployment realizado."
                    echo "Ejecutando rollback hacia:"
                    echo "$PREVIOUS_RELEASE"

                    ssh deploy@${DEPLOY_HOST} \
                        "ln -sfn $PREVIOUS_RELEASE ${APP_DIR}/current"

                    echo "Rollback completado"

                else
                    echo "No se realizó deployment. No es necesario rollback."
                fi
            '''
        }
    }
}
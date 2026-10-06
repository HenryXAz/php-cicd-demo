def deployTo(String environment, String host) {
    echo "Desplegando en ${environment} (${host})"

    // FIXED: Changed to triple double-quotes (""") so Groovy evaluates ${host}
    // Also used env.BUILD_NUMBER and escaped \$RELEASE so Bash handles it
    sh """
        RELEASE="release-${env.BUILD_NUMBER}"

        ssh deploy@${host} \\
            "mkdir -p /var/www/myapp/releases/\\$RELEASE"

        scp -r build/* \\
            deploy@${host}:/var/www/myapp/releases/\\$RELEASE/

        ssh deploy@${host} \\
            "ln -sfn /var/www/myapp/releases/\\$RELEASE /var/www/myapp/current"

        echo "Deployment ${environment} completado"
    """
}

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
            steps {
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
        }

        stage ('Deploy DEV') {
            steps {
                script {
                    deployTo('DEV', 'development')
                }
            }
        }

        stage ('Health Check DEV') {
            steps { // FIXED: Changed 'stage' to 'steps'
                sh '''
                    sleep 2
                    curl --fail --silent --show-error http://development/
                    echo
                    echo "DEV saludable"
                '''
            }
        }

        stage ('Deploy QA') {
            steps {
                script {
                    deployTo('QA', 'qa')
                }
            }
        }

        stage ('Health Check QA') {
            steps {
                sh '''
                    sleep 2
                    curl --fail --silent --show-error http://qa
                '''
                // FIXED: Removed the invalid empty 'echo'
                echo "QA saludable"
            }
        }

        stage ('Production Approval') {
            steps {
                input message: 'Desplegar esta versión en producción?',
                    ok: 'Deploy Production'
            }
        }

        stage ('Deploy PROD') {
            steps {
                script {
                    deployTo('PROD', 'production')
                }
            }
        }

        stage ('Health Check PROD') {
            steps {
                sh '''
                    sleep 2
                    curl --fail --silent --show-error http://production
                    echo
                    echo "PRODUCTION saludable"
                '''
            }
        }

    } // FIXED: Added this missing closing brace for 'stages'

    post {
        success {
            echo 'Pipeline completado correctamente.'
        }

        failure {
            echo 'Pipeline fallido. Revisa el ambiente y stage que produjo el error.'
        }

        aborted {
            echo 'Pipeline cancelado.'
        }
    }
}
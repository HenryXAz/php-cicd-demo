def deployTo(String environmentName, String host) {

    echo "Desplegando en ${environmentName} (${host})" 

    def commitSha = sh (
        script: 'git rev-parse --short HEAD',
        returnStdout: true 
    ).trim()

    def releaseName = "release-${commitSha}"

    echo "Release: ${releaseName}"

    sh """
        set -e

        PREVIOUS_RELEASE=\$(ssh deploy@${host} \
            "readlink -f /var/www/myapp/current || true")

        echo "Release anterior: \$PREVIOUS_RELEASE"

        echo "\$PREVIOUS_RELEASE" > previous_${environmentName}.txt

        ssh deploy@${host} \
            "mkdir -p /var/www/myapp/releases/${releaseName}"

        scp -r build/* \
            deploy@${host}:/var/www/myapp/releases/${releaseName}/

        ssh deploy@${host} \
            "test -f /var/www/myapp/shared/.env"

        ssh deploy@${host} \
            "ln -sfn /var/www/myapp/shared/.env /var/www/myapp/releases/${releaseName}/.env"

        ssh deploy@${host} \
            "ln -sfn /var/www/myapp/releases/${releaseName} /var/www/myapp/current"

        echo "Deployment ${environmentName} completado"
    """
}

def rollBack(String environmentName, String host) {
    echo "Ejecutando rollback de ${environmentName}"

    sh """
        PREVIOUS_RELEASE=\$(cat previous_${environmentName}.txt)

        if [ -n "\$PREVIOUS_RELEASE" ]; then

            echo "Restaurando \$PREVIOUS_RELEASE"

            ssh deploy@${host} \
                "ln -sfn \$PREVIOUS_RELEASE /var/www/myapp/current"

            echo "Rollback de ${environmentName} completado"

        else
            echo "No existe una release anterior."
            exit 1
        fi
    """
}

def healthCheck(String environmentName, String host) {
    echo "Verificando ${environmentName}"

    sh """
        sleep 2

        curl --fail \
            --silent \
            --show-error \
            http://${host}/

        echo
        echo "${environmentName} saludable"
    """
}

def cleanupReleases(String environmentName, String host) {
    echo "Limpiando releases antiguas en ${environmentName}"

    sh """
        ssh deploy@${host} '
            cd /var/www/myapp/releases

            ls -1dt release-* 2>/dev/null \
                | tail -n +6 \
                | xargs -r rm -rf 
        '
    """
}

pipeline {

    agent any

    options {
        disableConcurrentBuilds()

        buildDiscarder(
            logRotator(
                numToKeepStr: '20'
            )
        )
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

                    if find build -name '.env' -print -quit | grep -q .; then
                        echo "ERROR: Se encontró un archivo .env dentro del artefacto"
                        exit 1
                    fi

                    git rev-parse HEAD > build/REVISION
                    git rev-parse --short HEAD > build/REVISION_SHORT

                    echo "Contenido del artefacto:"
                    find build -maxdepth 2 -type f | head -50

                    echo "Commit empaquetado:"
                    cat build/REVISION
                '''
            }
        }

        stage ('Deploy DEV') {
            steps {
                sshagent(credentials: ['deploy-ssh-key']) {
                    script {
                        try {
                            deployTo('DEV', 'development')
                            healthCheck('DEV', 'development')
                            cleanupReleases('DEV', 'development')
                        } catch (Exception error) {
                            echo 'DEV falló. Ejecutando rollback...'

                            rollBack('DEV', 'development')

                            throw error 
                        }
                    }
                }
            }
        }

        stage ('Deploy QA') {
            steps {
                sshagent(credentials: ['deploy-ssh-key']) {
                    script {
                        try {
                            deployTo('QA', 'qa')
                            healthCheck('QA', 'qa')
                            cleanupReleases('QA', 'qa')
                        } catch (Exception error) {
                            echo 'QA falló. Ejecutando rollback...'

                            rollBack('QA', 'qa')

                            throw error
                        }
                    }
                }
            }
        }

        stage ('Production Approval') {
            steps {
                input message: 'Desplegar esta versión en producción??',
                    ok: 'Deploy Production'
            }
        }

        stage ('Deploy PROD') {
            steps {
                sshagent(credentials: ['deploy-ssh-key']) {
                    script {
                        try {
                            deployTo('PROD', 'production');
                            healthCheck('PROD', 'production')
                            cleanupReleases('PROD', 'production')
                        } catch (Exception error) {
                            echo 'PRODUCCIÓN falló. Ejecutando rollback...'

                            rollBack('PROD', 'production')

                            throw error
                        }
                    }
                }
            }
        }
    } 

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
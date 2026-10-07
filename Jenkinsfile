// def deployTo(String environmentName, String host) {
//     echo "Desplegando en ${environmentName} (${host})"

//     sh """
//         echo "Creando release-${env.BUILD_NUMBER}"

//         ssh deploy@${host} \
//             "mkdir -p /var/www/myapp/releases/release-${env.BUILD_NUMBER}"

//         scp -r build/* \
//             deploy@${host}:/var/www/myapp/releases/release-${env.BUILD_NUMBER}/

//         ssh deploy@${host} \
//             "ln -sfn /var/www/myapp/releases/release-${env.BUILD_NUMBER} /var/www/myapp/current"

//         echo "Deployment ${environmentName} completado"
//     """
// }

def deployTo(String environmentName, String host) {
    echo "Desplegando en ${environmentName} (${host})"

    sh """
        set -e

        RELEASE="release-${env.BUILDER_NUMBER}"

        echo "Release nueva: \$RELEASE"

        PREVIOUS_RELEASE = \$(ssh deploy@${host} \
            "readlink -f /var/www/myapp/current | | true")

        echo "Release anterior: \$PREVIOUS_RELEASE"

        echo "\$PREVIOUS_RELEASE" > previous_${environment}.txt

        ssh deploy@${host} \
            "mkdir -p /var/www/myapp/releases/\$RELEASE"

        scp -r buiid/* \
            deploy@${host}:/var/www/myapp/releases/\$RELEASE/

        ssh deploy@${host} \
            "ln -sfn /var/www/myapp/releases/\$RELEASE /var/www/myapp/current"

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
            echo "No existe un release anterior."
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
            http://${host}

        echo 
        echo "${environmentName} saludable"
    """
}

pipeline {

    agent any

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
                    try {
                        deployTo('DEV', 'development')
                        healthCheck('DEV', 'development')
                    } catch (Exception error) {
                        echo 'DEV falló. Ejecutando rollback...'

                        rollBack('DEV', 'development')

                        throw error 
                    }
                }
            }
        }

        // stage ('Deploy DEV') {
        //     steps {
        //         script {
        //             deployTo('DEV', 'development')
        //         }
        //     }
        // }

        // stage ('Health Check DEV') {
        //     steps { // FIXED: Changed 'stage' to 'steps'
        //         sh '''
        //             sleep 2
        //             curl --fail --silent --show-error http://development/
        //             echo
        //             echo "DEV saludable"
        //         '''
        //     }
        // }

        stage ('Deploy QA') {
            steps {
                script {
                    try {
                        deployTo('QA', 'qa')
                        healthCheck('QA', 'qa')
                    } catch (Exception error) {
                        echo 'QA falló. Ejecutando rollback...'

                        rollBack('QA', 'qa')

                        throw error
                    }
                }
            }
        }

        // stage ('Deploy QA') {
        //     steps {
        //         script {
        //             deployTo('QA', 'qa')
        //         }
        //     }
        // }

        // stage ('Health Check QA') {
        //     steps {
        //         sh '''
        //             sleep 2
        //             curl --fail --silent --show-error http://qa
        //         '''
        //         // FIXED: Removed the invalid empty 'echo'
        //         echo "QA saludable"
        //     }
        // }

        // stage ('Production Approval') {
        //     steps {
        //         input message: 'Desplegar esta versión en producción?',
        //             ok: 'Deploy Production'
        //     }
        // }

        stage ('Deploy PROD') {
            steps {
                script {
                    try {
                        deployTo('PROD', 'production');
                        healthCheck('PROD', 'production')
                    } catch (Exception error) {
                        echo 'PRODUCCIÓN falló. Ejecutando rollback...'

                        rollBack('PROD', 'production')
                        
                        throw error
                    }
                }
            }
        }

        // stage ('Deploy PROD') {
        //     steps {
        //         script {
        //             deployTo('PROD', 'production')
        //         }
        //     }
        // }

        // stage ('Health Check PROD') {
        //     steps {
        //         sh '''
        //             sleep 2
        //             curl --fail --silent --show-error http://production
        //             echo
        //             echo "PRODUCTION saludable"
        //         '''
        //     }
        // }

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
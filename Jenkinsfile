
library 'reference-pipeline'
void setBuildStatus(String message, String state, String refUrl, String stage) {

    withCredentials([string(credentialsId: 'Github_status_token_for_POD2_0', variable: 'GITHUB_TOKEN')]) {
        sh """curl --proxy "http://@internet.proxy.fedex.com:3128" -X POST -d '{"state":"${state}", "target_url": "${refUrl}","description": "${message}","context":"${stage}"}' -H "Authorization:Bearer ${GITHUB_TOKEN}" https://api.github.com/repos/FedEx/eai-3537131-fxo-ecommerce-platform/statuses/${env.GIT_COMMIT}"""
    }
}
pipeline {

    agent {
        label 'podman && !p1139559'
    }

    environment {

        TEAMS_WEBHOOK = "https://myfedex.webhook.office.com/webhookb2/96cb9040-e0ac-4937-9f0e-f6de569a7a1a@b945c813-dce6-41f8-8457-5a12c2fe15bf/IncomingWebhook/4bf87bd7bd0d4de68c0b9c505f8e3c8b/a9d659dc-b40f-4d5f-b212-30a3cf0f275b"

        EAI_NAME='fxo-ecommerce-platform'

        EAI_NUMBER='3537131'

        GIT_BRANCH = "${env.BRANCH_NAME}"

        EAI_FOLDER_NAME = "${EAI_NAME}-${EAI_NUMBER}"

        SONAR_PROJECT_KEY = "aaa-${EAI_NUMBER}-${EAI_NAME}"

        CF_APP_NAME='Print On Demand 2.0'

        notificationEmail="7615f35b.myfedex.onmicrosoft.com@amer.teams.ms"

        SONAR_URL="https://sonar.prod.cloud.fedex.com:9443/project/issues?branch=${env.BRANCH_NAME}&id=${SONAR_PROJECT_KEY}&resolved=false"

        MAGENTO_USERNAME=credentials('PUBLIC_ACCESS_KEY')

        MAGENTO_PASSWORD=credentials('PRIVATE_ACCESS_KEY')

        MIRAKL_USERNAME=credentials('MIRAKL_USERNAME')

        MIRAKL_PASSWORD=credentials('MIRAKL_PASSWORD')
    }

    options {
        disableConcurrentBuilds()
    }

    stages {

        stage('Initialize') {
            steps {
                script {

    				println "EAI Name is ${EAI_NAME}"
                    println "EAI Number is ${EAI_NUMBER}"
                    println "GIT Branch is ${GIT_BRANCH}"
                }
                setBuildStatus("Started", "pending","${BUILD_URL}","Unit Testing");
            }
        }

        stage('Unit Test') {
            agent {
                docker {
                    label 'docker'
                    image 'nexus2.prod.cloud.fedex.com:8444/fdx/eai3537131/adobe-commerce:2.4.7-p3-php-process'
                    reuseNode true
                    alwaysPull true
                }
            }
            steps {
                script {
                        FAILED_STAGE="${env.STAGE_NAME}"
                        CURRENT_STAGE="Unit Testing"

                }
                echo 'Creating auth.json...'

                sh '(envsubst < auth.json.template) > auth.json'

                sh 'COMPOSER_MEMORY_LIMIT=-1 composer install -vvv'

                sh 'php -dmemory_limit=-1 bin/magento setup:di:compile'

                echo 'Unit Testing Print on Demand...'

                sh 'XDEBUG_MODE=coverage ./vendor/phpunit/phpunit/phpunit -c phpunit.xml -d memory_limit=-1 --coverage-text=result.txt --whitelist app/code/Fedex --debug app/code/Fedex'

                sh '''

                    COVERAGE=`sed -n -E -e 's/^[[:space:]]*Lines[[:space:]]*:[[:space:]]*([0-9]+).*/\\1/p' result.txt`

                    echo "CODE COVERAGE: "$COVERAGE

                    if [[ $COVERAGE -lt 85 ]]
                    then
                        echo "Code Coverage is below minimum of 85%: ${COVERAGE}"
                        exit 1
                    fi
                '''

                setBuildStatus("Completed", "success","${BUILD_URL}","Unit Testing");
            }
        }

        //stage('SonarQube') {
//
		//	steps {
        //        setBuildStatus("Completed", "success","${BUILD_URL}","Unit Testing");
		//	    println "Running SonarQube"
        //        setBuildStatus("Started", "pending","${SONAR_URL}","SonarQube Analysis");
        //        script {
        //
        //            FAILED_STAGE="${env.STAGE_NAME}"
        //            CURRENT_STAGE="SonarQube Analysis"
        //
        //            sh "(envsubst < sonar-project.template) > sonar-project.properties"
//
//                    def scannerHome = tool 'SonarQube_Scanner';
//
//                    withSonarQubeEnv('SonarQube') {
//
//                        sh "${scannerHome}/bin/sonar-scanner -Dproject.settings=sonar-project.properties"
//                    }
//                }
//                setBuildStatus("Completed", "success","${SONAR_URL}","SonarQube Analysis");
//            }
//        }

        stage('NexusIQ') {

            when {
                anyOf {
                    branch 'staging3';
                }
            }
            steps {
                nexusPolicyEvaluation iqApplication: "${EAI_FOLDER_NAME}", iqStage: 'build', iqScanPatterns: [[scanPattern: 'composer.lock']]
            }
        }

        stage('Deploy') {

            when {
                anyOf {
                    branch 'staging2';
                }
            }
            steps {
                sh '''
                    WEBHOOK_URL="https://test.office.fedex.com/hooks/test.office.fedex.com-redeploy"

                    curl -m 30 --header "Content-Type: application/json" --request "POST" --data "{\\"ref\\": \\"refs/heads/${GIT_BRANCH}\\"}" --insecure ${WEBHOOK_URL}
                   '''
            }
        }
    }
     post {

        success {
            setBuildStatus("Build Successful", "success","${env.RUN_DISPLAY_URL}","Final Status");
            echo "Pipeline success"
            script {
                env.GIT_COMMIT_MSG = sh (script: 'git log -1 --pretty=%B ${GIT_COMMIT}', returnStdout: true).trim()

                office365ConnectorSend color: '#00FF00', message: "${CF_APP_NAME} branch-${env.GIT_BRANCH}: SUCCESSFUL", status: "Build Success", webhookUrl: TEAMS_WEBHOOK
            }
		}
		failure {
            setBuildStatus("Build failed at stage - ${FAILED_STAGE} ", "failure","${BUILD_URL}","Final Status");
            setBuildStatus("Completed", "failure","${BUILD_URL}","${CURRENT_STAGE}");
		    echo "Pipeline failed"
            script {
                env.GIT_COMMIT_MSG = sh (script: 'git log -1 --pretty=%B ${GIT_COMMIT}', returnStdout: true).trim()

                office365ConnectorSend color: '#FF0000', message: "${CF_APP_NAME} branch-${env.GIT_BRANCH}: FAILED", status: "Build Failure", webhookUrl: TEAMS_WEBHOOK
            }
        }
        cleanup {
            cleanWs()
        }
    }
}

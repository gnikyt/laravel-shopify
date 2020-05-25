pipeline {
    agent any


    parameters {
       
        choice(name: 'Environment',
            choices: ['PROD'],
            description: 'Deployment environment'
        )
        string(
            defaultValue: '',
            name: 'Deploy_Tag',
            description: 'Docker Tag to be promoted. NB: This value Can be retrived from a successfull master build)'
        )
    }

    stages {
        stage('Checkout git repo') {
            steps {
                checkout scm
                script{
                    env.git_commit = sh(returnStdout: true, script: 'git rev-parse HEAD').trim()
                }
            }
        } 

        stage('Deploy ECS Service') {
            steps {
                script{
                    String currentDate = new Date().format("yyyy-MM-dd")
                    def deploymentTag ="${currentDate}.${BUILD_NUMBER}.${env.git_commit}"
                    def stackName = "shopify-apps-ecs"
                    def domain_name = ""
                    def checkIfStackExists = sh(script: """aws cloudformation describe-stacks --stack-name ${stackName} --region us-east-1 >/dev/null""", returnStatus: true)
                    def cfnCommand = "create-stack"
                    def waitCommand = "stack-create-complete"
                    
                    if( checkIfStackExists == 0){
                        cfnCommand = "update-stack"
                        waitCommand = "stack-update-complete"
                    }
              
                    sh """
                    STACK_ID=\$(aws cloudformation ${cfnCommand} \
                        --stack-name ${stackName} \
                        --template-body file://infra/shopify-apps/cfn/webapp.yaml \
                        --parameters ParameterKey=AlbPath,ParameterValue=/ \
                            ParameterKey=AlbPriority,ParameterValue=1 \
                            ParameterKey=AlbStackName,ParameterValue=shopify-apps-alb \
                            ParameterKey=Cluster,ParameterValue=shopify-apps-cluster \
                            ParameterKey=ContainerPort,ParameterValue=80 \
                            ParameterKey=HealthCheckPath,ParameterValue=/ \
                            ParameterKey=HostedZoneName,ParameterValue=${domain_name} \
                            ParameterKey=Image,ParameterValue=018837131763.dkr.ecr.us-east-1.amazonaws.com/hopify-apps/webapp:${env.git_commit} \
                            ParameterKey=LoadBalancerPort,ParameterValue=80 \
                            ParameterKey=ServiceName,ParameterValue=${stackName} \
                            ParameterKey=Subdomain,ParameterValue=www \
                            ParameterKey=SubnetA,ParameterValue=subnet-b039209e \
                            ParameterKey=SubnetB,ParameterValue=subnet-4ada7807 \
                            ParameterKey=AlbSecurityGroup,ParameterValue=sg-5b123472 \ 
                            ParameterKey=VPC,ParameterValue=vpc-a1ddf2db \
                            ParameterKey=DeploymentTag,ParameterValue=${deploymentTag} \
                        --capabilities CAPABILITY_NAMED_IAM \
                        --output text \
                        --region us-east-1 \
                        --query "StackId")
                        
                        # wait untile the task is completed
                        aws cloudformation wait ${waitCommand} \
                        --stack-name \$STACK_ID \
                        --region us-east-1
                    """
                }
            }
        }
    }

    post {
        always {
            deleteDir() /* clean up our workspace */
        }
    }
}

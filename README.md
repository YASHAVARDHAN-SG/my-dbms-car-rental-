
<h1 >CAR RENTAL PORTAL </h1>
<body style="background-color:powderblue;">
<p>
  This is the car rental portal that is used to book a car for the journey according .<br>
  This the experimental project and it basic project done with the help of<em>HTML,CSS,PHP</em>  as <i>Front end development</i><br>
  and <em> MY SQL and PHP </em> for <i>Backend development</i>
                                                                                                                        
</p>
 
</body>
<hr>
<h1 style="textcolor:red;"> SCREEN SHOT </h1>



![Screenshot (79)](https://github.com/YASHAVARDHAN-SG/my-dbms-car-rental-/assets/137524620/3db6f388-35f6-4cc4-b8ef-233e8eae746a)

## Jenkins pipeline

The `Jenkinsfile` checks out this repository on the Jenkins agent labeled
`jenkins-slave-1`, starts an isolated SonarQube container, runs PHP_CodeSniffer
and SonarQube analysis in parallel containers, and archives a compressed source
artifact when both checks pass. It removes the SonarQube container and its
private Docker network after the build.

Configure the Jenkins job to use this repository's `Jenkinsfile` (for example,
as a Pipeline-from-SCM or Multibranch Pipeline job). To start a build on each
GitHub push, install the Jenkins GitHub plugin, make the Jenkins URL reachable
from GitHub, and add a webhook to this repository at
`https://<your-jenkins-url>/github-webhook/` with content type
`application/json` and the **Pushes** event enabled. The pipeline declares the
GitHub push trigger; verify the webhook's recent deliveries in GitHub after
configuration. The agent needs Docker CLI access to a running Docker daemon,
and must be a Linux/Unix agent. The pipeline downloads these images on first
use: `composer:2`, `sonarqube:lts-community`,
`sonarsource/sonar-scanner-cli:latest`, `curlimages/curl:8.12.1`, and
`alpine:3.21`. The Composer image installs PHPCS for the lint check; the Alpine
image packages the source artifact.

The Docker host must meet SonarQube's Elasticsearch requirements, including
`vm.max_map_count` of at least `524288`, and have enough memory for the
SonarQube container. The SonarQube container is build-local and not published on
a host port. Analysis settings are in `sonar-project.properties`; the pipeline
waits for the SonarQube quality gate. Because the server is removed after the
build, its dashboard and analysis history are temporary and are not retained.
The completed
`dist/car-rental-portal-<build-number>.tar.gz` is archived as a Jenkins build
artifact.

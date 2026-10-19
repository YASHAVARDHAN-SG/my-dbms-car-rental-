
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
`jenkins-slave-1`, starts an isolated SonarQube container, runs SonarQube
analysis in a scanner container, and archives a compressed source artifact when
analysis passes. It removes the SonarQube container and its private Docker
network after the build.

Configure the Jenkins job to use this repository's `Jenkinsfile` (for example,
as a Pipeline-from-SCM or Multibranch Pipeline job). To start a build on each
GitHub push, install the Jenkins GitHub plugin, make the Jenkins URL reachable
from GitHub, and add a webhook to this repository at
`https://<your-jenkins-url>/github-webhook/` with content type
`application/json` and the **Pushes** event enabled. The pipeline declares the
GitHub push trigger; verify the webhook's recent deliveries in GitHub after
configuration. The agent needs Docker CLI access to a running Docker daemon,
and must be a Linux/Unix agent. The pipeline downloads these images on first
use: `sonarqube:lts-community`, `sonarsource/sonar-scanner-cli:latest`,
`curlimages/curl:8.12.1`, and `alpine:3.21`. The Alpine image packages the
source artifact.

### Slack build notifications

The pipeline posts a start notification and a formatted success or failure
message to `#jenkins-jobs-car-rental`. To enable it:

1. Install the Jenkins **Slack Notification** plugin.
2. Create a Slack app in the workspace, add the `chat:write` bot token scope,
   install the app, and invite its bot user to `#jenkins-jobs-car-rental`.
3. In **Manage Jenkins → System → Slack**, configure the Slack workspace/team,
   the app's Bot User OAuth Token (or its Jenkins credential), and any required
   Slack API endpoint settings. Do not put the token in the `Jenkinsfile` or
   source repository.
4. Confirm the Slack Notification plugin's default integration configuration
   can post to the channel. Run a build and confirm the result message and
   build link appear there.

Slack delivery is best-effort: a Slack outage or configuration error will not
change the pipeline's build result.

GitHub delivers the webhook to the Jenkins controller; this is expected. The
Jenkinsfile schedules its pipeline stages on the `jenkins-slave-1` agent and
excludes nodes labeled `built-in` or `master`. In Jenkins, confirm that
`jenkins-slave-1` is assigned to the slave node (not the built-in node), that
the slave is online, and that the job is not restricted to the built-in node.
For a strict controller/slave separation, set the built-in node's executor
count to `0` under **Manage Jenkins → Nodes → Built-In Node → Configure**.
The first pipeline log line reports the node selected to run the stages.

The Docker host must meet SonarQube's Elasticsearch requirements, including
`vm.max_map_count` of at least `524288`, and have at least 4 GB of memory
available to Docker for SonarQube (in addition to memory needed by the Jenkins
agent and any other workloads). If the agent uses Docker Desktop or a VM, make
sure that VM has sufficient memory allocated. An Elasticsearch exit code of
137 means it was killed, commonly by the host out-of-memory killer; check
available Docker host memory and OOM events if startup fails. The pipeline logs
the container's OOM status and Docker host memory after a startup timeout. The
SonarQube container is build-local and not published on a host port. Analysis
settings are in `sonar-project.properties`; the pipeline waits for the
SonarQube quality gate. Because the server is removed after the build, its
dashboard and analysis history are temporary and are not retained.
The completed
`dist/car-rental-portal-<build-number>.tar.gz` is archived as a Jenkins build
artifact.

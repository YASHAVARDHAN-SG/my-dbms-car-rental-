
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
`jenkins-slave-1`, starts a persistent SonarQube container, runs SonarQube
analysis in a scanner container, archives a compressed source artifact, and
deploys the PHP site and MariaDB in Docker. The website listens on container
port 80 and is published on host port 8080 by default; SonarQube is published
on host port 9000 and stores its data in named Docker volumes. The database is
available only on the private `car-rental-app` Docker network and persists in
the `car-rental-db-data` volume. Both application containers use Docker's
`unless-stopped` restart policy. Website deployments reuse the
`car-rental-portal:latest` image tag instead of accumulating a new tagged image
for each build. Before replacing images, the pipeline archives existing
`car-rental-portal` image tags under `/home/ubuntu/docker_images/`, then removes
the old tags after a successful deployment. A rollback image is retained while
the new website is being health-checked.

#### Database setup and persistence

Create two Jenkins **Secret text** credentials before deploying:

- `car-rental-db-root-password`: a strong MariaDB root password.
- `car-rental-db-password`: a strong password for the restricted application
  account `car_rental_app`.

Do not place these secrets in source control. On the first deployment with a
new `car-rental-db-data` volume, the pipeline starts MariaDB 11.4 and imports
`carrental.sql`, which creates the application tables and a small generic
vehicle catalogue. It does not create a default administrator or demo account.
The PHP application receives its database connection settings from container
environment variables.

MariaDB runs the initialization script only when its data directory is empty.
For safety, the pipeline never deletes or resets the database volume. If a
database container or volume already exists, verify that the Jenkins
credentials match the existing database and back up and migrate its schema
before deploying. The pipeline checks for the required tables and stops rather
than overwriting an incompatible database. Changing a Jenkins password
credential does not change the password inside an existing MariaDB instance;
rotate that password in MariaDB and Jenkins together.

The `Dockerfile` packages the PHP site with Apache and PDO MySQL support. Set
the Jenkins `WEBSITE_PORT` parameter to an available host port (defaults to
`8080`; use `80` only if it is free). Allow that port through the agent host's
firewall/network rules. Visit
`http://<jenkins-slave-1-host>:<WEBSITE_PORT>/` after a successful deployment.
The pipeline briefly stops the current website container to free the port,
then starts and health-checks the replacement. On a failed deployment it
attempts to restore the prior website image while keeping the database volume
intact. Archived images can be restored with
`docker load --input /home/ubuntu/docker_images/<archive-name>.tar`.

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
`curlimages/curl:8.12.1`, `alpine:3.21`, and `mariadb:11.4`. The Alpine image
packages the source artifact and removes stale workspace files at checkout,
including files left by previous containers with different ownership. The
scanner and packager run as the Jenkins agent's UID/GID so their workspace
files remain removable by the agent on later builds.

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

### Accessing SonarQube

After the pipeline starts SonarQube on `jenkins-slave-1`, open
`http://<jenkins-slave-1-host>:9000` in a browser. Replace the placeholder with
the slave's reachable IP address or DNS name. Allow inbound TCP port 9000 in
the host firewall and cloud security rules; restrict access to trusted IPs
rather than exposing the administration UI publicly.

On a fresh SonarQube instance, sign in with `admin` / `admin` and immediately
change the password when prompted. In SonarQube, create a user token from
**My Account → Security → Generate Tokens**. Add the token to Jenkins as a
**Secret text** credential with ID `sonarqube-token`; the pipeline uses it for
analysis and does not store it in the repository. The initial build may stop at
the analysis stage until this Jenkins credential is configured.

SonarQube restarts automatically and its data persists between builds. The
Docker host must keep port 9000 available. To inspect the container on the
slave, use `docker ps --filter name=car-rental-sonarqube` and
`docker logs car-rental-sonarqube`.

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
the container's OOM status and Docker host memory after a startup timeout.
Analysis settings are in `sonar-project.properties`; the pipeline waits for
the SonarQube quality gate, and a failed gate prevents deployment. Configure
PHP coverage is generated before analysis: `Dockerfile.test` builds a PHP 8.2,
PHPUnit, and Xdebug runner; the `PHP Tests and Coverage` stage runs the tests
and writes `coverage.xml`; and `sonar-project.properties` tells SonarQube to
import that Clover report. The initial test suite exercises the database-free
shared helpers in `includes/helpers.php`. This produces real coverage data, but
does not guarantee that the project's new-code coverage threshold passes:
SonarQube reports the coverage of all new PHP lines, so add tests for additional
application logic until the configured gate is met. A missing/empty report or
failed test stops the pipeline before analysis and deployment.
The completed
`dist/car-rental-portal-<build-number>.tar.gz` is archived as a Jenkins build
artifact.
######
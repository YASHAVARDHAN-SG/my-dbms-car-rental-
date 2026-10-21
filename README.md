
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

## CircleCI pipeline

The CircleCI configuration is [.circleci/config.yml](./.circleci/config.yml).
It checks out the repository, starts or reuses persistent SonarQube, runs
PHPUnit with Xdebug coverage, waits for the SonarQube Quality Gate, creates and
stores a source artifact, then deploys the PHP site and MariaDB. A failed test
or quality gate stops the workflow before artifact creation and deployment.

### Keep builds on the existing server

This pipeline targets the CircleCI self-hosted runner resource class
`yashavardhan-sg/car-rental-runner`, matching the current Jenkins slave
deployment host. Register that resource class in the CircleCI organization and
install its Linux runner service on the retained server. The runner service
account must have Docker CLI access to the host Docker daemon; restrict this
access because Docker control is effectively host-level access. Connect the
CircleCI project to this GitHub repository and enable push-triggered workflows.
No Jenkins controller is required once the CircleCI runner is operational.

The workflow order is checkout, SonarQube startup, PHP tests and coverage,
SonarQube analysis, artifact creation, and Docker deployment. The test stage
builds `Dockerfile.test` and produces Clover coverage at `coverage.xml`; the
scanner imports the report using `sonar-project.properties`. It archives
`dist/car-rental-portal-<build-number>.tar.gz` as a CircleCI artifact.

### CircleCI environment variables

Add these values in CircleCI project settings or a context attached to the job:

- `SONAR_TOKEN`: SonarQube analysis token.
- `DB_ROOT_PASSWORD`: strong MariaDB root password.
- `DB_PASSWORD`: strong password for the restricted `car_rental_app` user.
- `WEBSITE_PORT`: optional host port (defaults to `8080`; use `80` only if
  available).
- `SLACK_WEBHOOK_URL`: optional Slack incoming-webhook URL. Notifications are
  skipped if unset and are best-effort if delivery fails.
- `DOCKER_IMAGE_ARCHIVE_DIR`: optional path for saved previous image archives
  (defaults to `$HOME/docker_images` on the runner host).

Do not commit these values to the repository. The Slack webhook should be
stored as a secret environment variable.

### Database and persistent services

On first deployment with an empty `car-rental-db-data` volume, MariaDB 11.4
imports `carrental.sql`, which creates the application schema and generic
vehicle seed data; it creates no default administrator or demo accounts.
MariaDB is only exposed on Docker network `car-rental-app`. SonarQube persists
in Docker volumes `car-rental-sonarqube-data`,
`car-rental-sonarqube-extensions`, and `car-rental-sonarqube-logs`; its web UI
is published on host port 9000.

The CircleCI runner uses the retained host's Docker daemon, so existing
containers and named volumes on that host remain available after Jenkins is
decommissioned. The workflow never deletes database volumes. MariaDB executes
the initialization SQL only on an empty data directory; if the existing
database container/volume uses a different password or schema, back it up and
migrate it before the first CircleCI deployment. A Jenkins environment
credential does not automatically become a CircleCI environment variable.

Allow the website port through the host firewall and visit
`http://<runner-host>:<WEBSITE_PORT>/`. The host must keep port 9000 available
for SonarQube and meet its Elasticsearch requirements, including
`vm.max_map_count` of at least `524288` and at least 4 GB of memory for
SonarQube. Restrict access to port 9000 to trusted IPs. Inspect persistent
services with `docker ps` and `docker logs car-rental-sonarqube`.

The website image is health-checked before the new container is renamed to
`car-rental-portal`; failed deployment attempts restore the previous image
when one exists. Previous image archives are saved on the runner host. Keep
the `Jenkinsfile` until the CircleCI pipeline has passed and the deployment has
been verified; it can then be removed as a separate cleanup.
######
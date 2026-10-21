
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

## CircleCI

The starter pipeline is defined in [.circleci/config.yml](./.circleci/config.yml).
This repository is one PHP 8.2 project. Composer installs the PHPUnit
development dependency, and the pipeline caches `vendor` using `composer.json`
because the repository does not currently have a `composer.lock` file. It
builds the application image with the repository `Dockerfile`, runs a homepage
smoke test against that built image, and executes
`vendor/bin/phpunit --bootstrap tests/bootstrap.php --no-coverage tests/HelpersTest.php`.
There is no project lint script, so linting is omitted. This starter workflow
does not deploy or need secrets.

Connect the GitHub repository to CircleCI and enable the project workflow to
run on pushes. The `cimg/php:8.2` Docker image supplies PHP and Composer; no
CircleCI self-hosted runner or Jenkins server is needed for this starter test
job.
######
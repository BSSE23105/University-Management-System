# University-Management
DBMS Project and Open Ended Lab

# Path
Make Sure that you put this folder in the wap64/www/ folder if you are using wampserver

# To Run
http://localhost/UMS/UMS-Code/index.php


# Important
# Project Structure and Execution Steps

                         ----------------Admin Portal----------------
1) Only Admin has the Control to Register Users (including Students and Faculty Member).
2) So to login as student or faculty member you have to register them first through admin portal.
3) Admin Name: `Administrator`
   Admin Email: `admin@itu.edu.pk`
   Admin Password: `Admin@123`
4) After Successful Login, You can Perform Crud Functions in multiple modules within Admin Portal such as in `User management`, `Course Registeration`, `Messaging Module`. and normal Operations such as viewing schedule of all faculty staff.
5) --User management is to register faculty and student (which include their signin info as well as personal info)
   --Course Registeration allow admin to register new courses with the info of whom in the faculty staff would be teaching that course.
   --Messgaing module allow admin to send messages to student as well as faculty member based on their ids(Names are shown) that are available in the database

                        ----------------Faculty Portal----------------

1) Once faculty member(teacher) is registered in the User management module within admin portal.
2) From that registeration info, the name, Id or email and password would be required to login as that particular faculty member
3) After being logged in, faculty member have a choice in performing different operations like checking his courses, that he is being assigned by the admin from the course module.
4) He can set his schedule of delivering different courses schedule. that will also be shown to the admin in the schedule part
5) He can sent message to the admin on a fixed email and to the students with the help of their specific ids(Names are shown) from the database.

                        ----------------Student Portal----------------

1) Once Student is registered in the User management module within admin portal.
2) From that registeration info, the name, Id or email and password would be required to login as that particular Student member
3) After being logged in, Student have a choice in performing different operations like checking his courses, that he chosse to be enrolled in.
4) He can also enroll in the courses that are available and made by the Admin.
5) He can sent message to the admin on a fixed email and to the Faculty members with the help of their specific ids(Names are shown) from the database.

moodle-mod_exam
===============
Moodle activity module based on quiz

Exam module is a successor to original 'Quiz' module.
Main purpose of this plugin is to improve performance of quiz,
so that a large number of users can attempt quiz simultaneously. 
Only multiple choice questions are supported, if quiz contains 
any other type question then it will be skipped by this plugin automatically.
Most of the settings of this module will be same as Quiz.

Istallation
============
* Unzip exam.zip and place in mod folder.
* Go to notification page to intall it.

Create exam
============
* Since this module depends on Quiz, Fist create a quiz (only with multiple choice questions) for exam.
* Create an Exam activity and do following settings-
	- Select quiz - select an existing quiz form dropdown.
	(Only multiple choice question is supported rest will be skipped) 
	- Marks - default marks is 100. You can change it according to question.
	- Do other settings (grade, completion) and save it.

Note:- You can also use a hidden or orphaned quiz for exam.

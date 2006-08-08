<?php
/*
Copyright 2005 Simone Gammeri, Francesco Di Cerbo, LIPS Lab University of Genova, ITALY

This file is part of MoodleSoapPlugin.

MoodleSoapPlugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

MoodleSoapPlugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with MoodleSoapPlugin; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
*/

	require_once("config.php");
	require_once("course/lib.php");
	require_once("$CFG->libdir/blocklib.php");
/***

	MoodlePluginException class provides MoodlePlugin for an exception class.
	
***/

	class MoodlePluginException extends Exception
	{
		public function __construct($message, $code = 0) 
		{
    	   parent::__construct($message,$code);
   		}
	
    	public function __toString() 
		{
	    	return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
   		}
	}

/***

	MoodlePlugin class provides Moodle for an API interface.
	
	Import and export operations lean on XML validated documents.
	
	Each entity selection - user, course or category - is accomplished through a database
	cross-match, so that users are avoided from any possible error.

***/
	class MoodlePlugin
	{	
		
	/*** 	
		
		This method imports inside Moodle a users' list.
		If a course is specified by its id and shortname, it gets all the inserted users also
		enrolled in such course.

		Returns an int which stands for the real count of inserted - and enrolled - users.
		
		@param	string 	$xml		The string containing the XML whinch specifies users' list
									This string will e validated by "users.xsd".
		@param	int	 	$courseID	The id number of the course for student enrolment.
		@param	string	$shortname	The shortname of the course for student enrolment.

	***/	
		public function importUsers($xml,$courseID=null,$shortname=null)
		{
			//DOM parsing and validating
			$dom=new DOMDocument();
			$dom->loadXML($xml);
			if (!(@$dom->schemaValidate($this->users_schema)))
			{
				throw new MoodlePluginException("ERROR, not valid XML data...",2);
			}
			$this->error[$this->users_table]=array();
			//Create new users
			$count=0;
			$users=$dom->getElementsByTagName("user");
			foreach ($users as $user)
			{
				$newuser=new object();
				while($child=$user->firstChild)
				{
					$user->removeChild($child);
					switch($key=$child->nodeName)
					{
						case"moodle_oriented":
							while($m_o_child=$child->firstChild)
							{
								$child->removeChild($m_o_child);
								switch($key=$m_o_child->nodeName)
								{
									case"id":
										//ID must be assigned later
										break;
									case"auth":
										$newuser->$key="XML-massive"; //New auth type 
										break;
									case"picture":
										$newuser->$key=null;
										break;
									default:
										$newuser->$key=$m_o_child->textContent;
										break;
								}
							}
							break;
						case"user_description":
							while($u_d_child=$child->firstChild)
							{
								$child->removeChild($u_d_child);
								switch($key=$u_d_child->nodeName)
								{
									case"it_CF": 
									case"birthdate":
										//future extensions
										break;
									default:
										$newuser->$key=$u_d_child->textContent;
										break;
								}
							}		
							break;
						default:
							$newuser->$key=$child->textContent;
							break;
					}
				}
				//Try to give an ID to the new user and then insert it in databas
				if ($newuser->id = insert_record($this->users_table, $newuser)) 
				{
					$count++;

					//If a courseID is specified, then try an enrolment
					if ($courseID!=null and $shortname!=null and record_exists($this->courses_table,"id",$courseID,"shortname",$shortname))
					{
						enrol_student($newuser->id,$courseID);
					}
				}	
				else 
				{
					array_push($this->error[$this->users_table],$newuser->username);
				}
			}
			return $count;
		}	

	/***
		
		This method imports inside Moodle a courses' list.
		A category must be specified by its id and name, so that inserted courses will belong
		to such category.

		Returns an int which stands for the real count of inserted courses.
		
		@param	string 	$xml		The string containing the XML whinch specifies courses' list
									This string will e validated by "courses.xsd".
		@param	int	 	$catID		The id number of the category.
		@param	string	$catName	The shortname of the category.
		
	***/
		public function importCourses($xml,$catID,$catName)
		{
			//DOM parsing and validating
			$dom=new DOMDocument();
			$dom->loadXML($xml);
			if (!(@$dom->schemaValidate($this->courses_schema)))
			{
				throw new MoodlePluginException("ERROR, not valid XML data...",2);
			}
			//Check if category exists
			if (!record_exists($this->categories_table,"id",$catID,"name",$catName))
			{
				throw new MoodlePluginException("ERROR, category not found...",1);
			}
			$this->error[$this->courses_table]=array();
			//Create new courses
			$count=0;
			$courses=$dom->getElementsByTagName("course");
			foreach ($courses as $course)
			{
				$newcourse=null;
				while($child=$course->firstChild)
				{
					$course->removeChild($child);
					switch($key=$child->nodeName)
					{
						case"id":
							//ID must be assigned later
							break;
						case "category":
							$newcourse->$key=$catID;
							break;
						default:
							$newcourse->$key=$child->textContent;
							break;
					}
				}
				//Try to give an ID to the new course and then insert it in database
				if($newcourseid = insert_record($this->courses_table,$newcourse)) 
				{  
					$page = page_create_object(PAGE_COURSE_VIEW, $newcourseid);
					blocks_repopulate_page($page); 
					// Set up new course
					$section = NULL;
		        	        $section->course = $newcourseid;   // Create a default section.
            				$section->section = 0;
					$section->id = insert_record($this->course_sections_table, $section);
					$count++;
				}
				else 
				{
					array_push($this->error[$this->courses_table],$newcourse->shortname);
				}			
			}
			return $count;
		}

	/***

		This method imports inside Moodle a categories' tree.
		If a category is specified by its id and name, the tree is imported under such category.
		If not the tree will be inserted under root element.

		Returns an int which stands for the real count of inserted categories.
		
		@param	string 	$xml		The string containing the XML whinch specifies categories' list
									This string will be validated by "courses.xsd".
		@param	int	 	$catID		The id number of the category.
		@param	string	$catName	The shortname of the category.
		
	***/
		public function importCategories($xml,$catID=0,$catName=null)
		{
		//DOM parsing and validating
			$dom=new DOMDocument();
			$dom->loadXML($xml);
			if (!(@$dom->schemaValidate($this->categories_schema)))
			{
				throw new MoodlePluginException("ERROR, not valid XML data...",2);
			}

			//Check if category exists
			if ($catId!=0 and $catname!=null and (!record_exists($this->categories_table,"id",$catID,"name",$catName)))
			{
				throw new MoodlePluginException("ERROR, category not found...",1);
			}
			$this->error[$this->categories_table]=array();

			//Create new categories
			$count=0;
			$ids=null;
			$categories=$dom->getElementsByTagName("category");
			foreach ($categories as $category)
			{
				$newcategory=null;
				while($child=$category->firstChild)
				{
					$category->removeChild($child);
					switch($key=$child->nodeName)
					{
						case"id":
							$id=$child->textContent;
							//ID must be assigned later
							break;
						case "parent":
							$parent=$child->textContent;
							//$newcategory->$key= (isset($ids[$parent]))? $ids[$parent] : $catID;
							
							if (isset($ids[$parent]))
							{
								$newcategory->$key=$ids[$parent];
							}
							else
							{
								$newcategory->$key=$catID;							
							}
							break;
						default:
							$newcategory->$key=$child->textContent;
							break;
					}
				}
				//Try to give an ID to the new category and then insert it in database
				if($newcategoryid = insert_record($this->categories_table,$newcategory)) 
				{  
					$count++;
					$ids[$id]=$newcategoryid;				
				}
				else 
				{
					array_push($this->error[$this->categories_table],$newcategory->name);
				}			
			}
			return $count;
		}
	/*** 	
		
		This method enrols inside Moodle a users' list.
		A course must be specified by its id and shortname.

		Returns an int which stands for the real count of enrolled users.
		
		@param	string 	$xml		The string containing the XML whinch specifies users' list
		@param	int	 	$courseID	The id number of the course for student enrolment.
		@param	string	$shortname	The shortname of the course for student enrolment.

	***/	
		public function enrolStudents($xml,$courseID,$shortname)
		{
			//Verify if course exists
			$found=false;
			if (!record_exists($this->courses_table,"id",$courseID,"shortname",$shortname))
			{
				// Return ERROR message
				throw new MoodlePluginException("ERROR, course not found...",1);
			}
			
			//Create DOMDocument
			$doc=new DOMDocument();
			$doc->loadXML($xml);
			$students= $doc->documentElement->getElementsByTagName("user");
			$count=0;
			$this->error[$this->users_table]=array();
			
			//Enrol students
			foreach ($students as $student)
			{
				$usernames=$student->getElementsByTagName("username");
				$username=$usernames->item(0)->textContent;
				$m_ors=$student->getElementsByTagName("moodle_oriented");
				$ids=$m_ors->item(0)->getElementsByTagName("id");
				$id=$ids->item(0)->textContent;
				if (record_exists("user","id",$id,"username",$username))
				{
					if (enrol_student($id,$courseID))
					{
						$count++;
					}
					else 
					{
						array_push($this->error[$this->users_table],$username);
					}
				}
				else 
				{
					array_push($this->error[$this->users_table],$username);
				}
				
			}
			
			//Return count of enrolled students
			return $count;
		}

	/*** 	
		
		This method exports from Moodle a users' list.
		If a course is specified by its id and shortname, it gets only the teachers and students in such course.

		Returns a string which contains a valid xml document.
		
		@param	int	 	$courseID	The id number of the course. 
		@param	string	$shortname	The shortname of the course.

	***/	
		public function exportUsers($courseID=1,$shortname=null)
		{
			if ($courseID!=1 and $shortname!=null)
			{
				//Verify if course exists
				if (!record_exists($this->courses_table,"id",$courseID,"shortname",$shortname))
				{
					// Return ERROR message
					throw new MoodlePluginException("ERROR, course not found...",1);
				}
			}
			
			//Get course students and teachers
			$teachers=get_course_teachers($courseID);
			$students=get_course_students($courseID);
			$courseUsers=$teachers+$students;

			//Create new DOMDocument
			$xml = new DOMDocument("1.0");
			$xml->formatOutput=true;
			$root = $xml->createElement("users");
			foreach ($courseUsers as $courseUser)
			{
				$ele_user = $xml->createElement("user");
				$ele_user_description = $xml->createElement("user_description");
				$ele_moodle_oriented = $xml->createElement("moodle_oriented");
				
				//Assign role to user
				if (in_array($courseUser,$teachers))
				{
					$role="teacher";
				}
				else
				{
					$role ="student";
				}
				$att_role= new DOMAttr("role",$role);
				$ele_user->setAttributeNode($att_role);
				
				//Get every information about users
				$user=get_record($this->users_table,"id",$courseUser->id);
				foreach($user as $key => $value)
				{
					$child = $xml->createElement($key,$value);
					switch($key)
					{
						case"username":
						case"password":
						case"firstname":
						case"lastname":
						case"email":
							$ele_user->appendChild($child);
							break;
						case"id":
						case"auth":
						case"confirmed":
						case"deleted":  
						case"idnumber":
						case"emailstop":
						case"firstaccess":
						case"lastaccess":
						case"lastlogin":
						case"currentlogin":
						case"lastIP":
						case"secret":
						case"picture":
						case"mailformat":
						case"maildigest":
						case"maildisplay":
						case"htmleditor":
						case"autosubscribe":
						case"timemodified":
							$ele_moodle_oriented->appendChild($child);
							break;
						case"icq":
						case"phone1":
						case"phone2":
						case"institution":
						case"department":
						case"address":
						case"city":
						case"country":
						case"lang":
						case"timezone":
						case"url":
						case"description":
						case"it_CF":
						case"birthdate":
							$ele_user_description->appendChild($child);
							break;
					}		
				}
				$ele_user->appendChild($ele_moodle_oriented);
				$ele_user->appendChild($ele_user_description);
				$root->appendChild($ele_user);
			}
			$xml->appendChild($root);

			//Return a string containing the XML Document
			$xml->save($this->users_xml);
			$f=fopen($this->users_xml,r);
			$str=fread($f, filesize($this->users_xml));
			fclose($f);
			return $str;

		}
		
	/*** 	
		
		This method exports from Moodle a courses' list.
		If a course is specified by its id and shortname, it gets only the course.
		If a category is specified by its id and name, it gets only the courses inside such category.

		Returns a string which contains a valid xml document.
		
		@param	int	 	$courseID	The id number of the course. 
		@param	string	$shortname	The shortname of the course.
		@param	int	 	$catID		The id number of the category. 
		@param	string	$catName	The shortname of the category.

	***/	
		public function exportCourses($catID=null,$catName=null,$courseID=null,$shortname=null)
		{
			$courses=array();
			if ($courseID==null and $shortname==null)
			{
				if ($catID==null and $catName==null)
				{
					foreach ($this->moodle_courses as $course)
					{
						array_push($courses,$course);
					}
				}
				else
				{
					if (!record_exists($this->categories_table,"id",$catID,"name",$catName))
					{
						//Return ERROR message
						throw new MoodlePluginException("ERROR, category not found...",1);
					}
					foreach ($this->moodle_courses as $course)
					{
						if ($course->category==$catID)
						{
							array_push($courses,$course);
						}
					}

				}
		
			}
			else
			{
				//Verify if course exists
				if (!record_exists($this->courses_table,"id",$courseID,"shortname",$shortname))
				{
					//Return ERROR message
					throw new MoodlePluginException("ERROR, course not found...",1);
				}
				$course=get_record($this->courses_table,"id",$courseID);
				array_push($courses,$course);
			}


			//Create new DOMDocument
			$xml = new DOMDocument("1.0");
			$xml->formatOutput=true;
			$root = $xml->createElement("courses");
			
			//Get every information on courses
			foreach ($courses as $course)
			{
				$ele_course = $xml->createElement("course");
				foreach($course as $key => $value)
				{
					$child = $xml->createElement($key,$value);
					$ele_course->appendChild($child);
				}
				$root->appendChild($ele_course);
				
			}
			$xml->appendChild($root);
			
			//Return a string containing the XML Document
			$xml->save($this->courses_xml);
			$f=fopen($this->courses_xml,r);
			$str=fread($f, filesize($this->courses_xml));
			fclose($f);
			return $str;
		}
		
	/*** 	
		
		This method exports from Moodle a categories' tree.
		If a category is specified by its id and name, it gets only the tree child of such category.

		Returns a string which contains a valid xml document.
		
		@param	int	 	$catID		The id number of the category. 
		@param	string	$catName	The shortname of the category.

	***/	
		public function exportCategories($catID=0,$name=null)
		{
			$categories=array();
			$arrayID=array();
			if ($catID==0 and $name==null)
			{
				foreach ($this->moodle_categories as $category)
				{
					if ($category->parent == $catID)
					{
						array_push($categories,$category);
						array_push($arrayID,$category->id);
					}
				}
			}
			else
			{
				if (!record_exists($this->categories_table,"id",$catID,"name",$name))
				{
					//Return ERROR message
					throw new MoodlePluginException("ERROR, category not found...",1);
				}
				$category=get_record($this->categories_table,"id",$catID);
				array_push($categories,$category);
				array_push($arrayID,$category->id);
			}
			
			//Get all the child-tree
			$childExist=true;
			while($childExist)
			{
				$childExist=false;
				$temp=array();
				
				foreach ($categories as $t_category)
				{
					foreach ($this->moodle_categories as $moodle_category)
					{
						if (($t_category->id == $moodle_category->parent) and (!in_array($moodle_category->id,$arrayID)))
						{
							array_push($temp,$moodle_category);
							array_push($arrayID,$moodle_category->id);
							$childExist=true;
						}
					}
				}
				$categories=array_merge($categories,$temp);
			}
			
			//Create new DOMDocument
			$xml = new DOMDocument("1.0");
			$xml->formatOutput=true;
			$root = $xml->createElement("categories");
			
			//Get every information about categories
			foreach ($categories as $category)
			{
				$ele_cat = $xml->createElement("category");
				foreach($category as $key => $value)
				{
					$child = $xml->createElement($key,$value);
					$ele_cat->appendChild($child);
				}
				$root->appendChild($ele_cat);
			}
			$xml->appendChild($root);
			
			//Return a string containing the XML Document
			$xml->save($this->categories_xml);
			$f=fopen($this->categories_xml,r);
			$str=fread($f, filesize($this->categories_xml));
			fclose($f);
			return $str;
		}
		
	/*** 	
		
		This method gets last error in importing and enrolling users, importing courses an importing categories.

		Returns an array containing the usernames, the course shortnames or the category names which caused
		last error.

		@param	string	$errorType	Can be: "user", "course" o "course_categories"

	***/	
		
		public function getFailed($errorType)
		{
			$str=string();
			foreach ($this->error[$errorType] as $e)
			{
				$str=$str+$e+"<br>";
			}
			return $str;
		}
		
		public function __construct()
		{
			$this->moodle_courses=get_courses();
			$this->moodle_users=get_users();
			$this->moodle_categories=get_categories();
			$this->users_schema="users.xsd";
			$this->users_xml="users.xml";
			$this->users_table="user";
			$this->courses_schema="courses.xsd";
			$this->courses_xml="courses.xml";
			$this->courses_table="course";
			$this->course_sections_tables="course_sections";
			$this->categories_schema="categories.xsd";
			$this->categories_xml="categories.xml";
			$this->categories_table="course_categories";
			$this->error[$this->users_table]=array();
			$this->error[$this->courses_table]=array();
			$this->error[$this->categories_table]=array();
		}
		
		private $moodle_courses;
		private $moodle_users;
		private $moodle_categories;
		private $users_schema;
		private $users_xml;
		private $users_table;
		private $courses_schema;
		private $courses_xml;
		private $courses_table;
		private $course_sections_tables;
		private $categories_schema;
		private $categories_xml;
		private $categories_table;
		private $error;
		
	}
?>

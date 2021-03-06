<?php

class Post_Controller extends Controller {
	
	/*
	 * Show the posts
	 */
	public function index($params){
		$this->setView('index.php');
		
		$is_logged = isset(User_Model::$auth_data);
		$is_student = $is_logged && isset(User_Model::$auth_data['student_number']);
		$is_admin = $is_logged && User_Model::$auth_data['admin']=='1';
		$category = isset($params['category']) ? $params['category'] : null;
		
		$category_model = new Category_Model();
		
		$this->set(array(
			'is_logged'			=> $is_logged,
			'is_student'		=> $is_student,
			'is_admin'			=> $is_admin,
			'categories'		=> $category_model->getAll(),
			'current_category'	=> $category
		));
		
		// If the user is logged
		if($is_logged)
			$this->set(array(
				'username'		=> User_Model::$auth_data['username'],
				'groups_auth'	=> Group_Model::getAuth(),
				// Non-official posts
				'posts'			=> $this->model->getPosts(array(
					'restricted'	=> true,
					'official'		=> false,
					'category_name'	=> $category,
					'show_private'	=> $is_student
				), Config::POST_DISPLAYED)
			));
		
		// If the user is a student
		if($is_student)
			$this->set(array(
				'firstname'		=> User_Model::$auth_data['firstname'],
				'lastname'		=> User_Model::$auth_data['lastname'],
				'avatar_url'	=> User_Model::$auth_data['avatar_url']
			));
		
		
		// Official posts
		$this->set('official_posts', $this->model->getPosts(array(
			'restricted'	=> true,
			'official'		=> true,
			'category_name'	=> $category,
			'show_private'	=> $is_student
		), Config::POST_DISPLAYED));
		
		// Events
		$event_model = new Event_Model();
		$this->set(array(
			'events' 			=> $event_model->getByMonth((int) date('Y'), (int) date('n'), array(
				'official'			=> $is_logged ? null : true,
				'show_private'		=> $is_student
			)),
			'calendar_month'	=> (int) date('n'),
			'calendar_year'		=> (int) date('Y')
		));
		
	}
	
	
	/*
	 * Show the posts by Ajax
	 */
	public function index_ajax($params){
		$this->setView('index_ajax.php');
		
		$is_logged = isset(User_Model::$auth_data);
		$is_student = $is_logged && isset(User_Model::$auth_data['student_number']);
		$is_admin = $is_logged && User_Model::$auth_data['admin']=='1';
		
		$category = isset($params['category']) ? $params['category'] : null;
		$user_id = isset($params['user_id']) ? $params['user_id'] : null;
		$offset = (((int) $params['page']) - 1) * Config::POST_DISPLAYED;
		
		$this->set(array(
			'is_logged'			=> $is_logged,
			'is_student'		=> $is_student,
			'is_admin'			=> $is_admin
		));
		
		// If the user is logged in
		if($is_logged){
			
			$this->set(array(
				'username'			=> User_Model::$auth_data['username'],
				'groups_auth'	=> Group_Model::getAuth()
			));
			
			// Non-official posts
			if(isset($params['official']) && $params['official'] == '0')
				$this->set('posts', $this->model->getPosts(array(
					'restricted'	=> true,
					'official'		=> false,
					'category_name'	=> $category,
					'user_id'		=> $user_id,
					'show_private'	=> $is_student
				), Config::POST_DISPLAYED, $offset));
			
		}
		if($is_student)
			$this->set(array(
				'firstname'		=> User_Model::$auth_data['firstname'],
				'lastname'		=> User_Model::$auth_data['lastname'],
				'avatar_url'	=> User_Model::$auth_data['avatar_url']
			));
		
		
		// Official posts
		if(!isset($params['official']) && isset($params['group'])){
			$this->set('posts', $this->model->getPosts(array(
				'restricted'	=> true,
				'group_name'	=> $params['group'],
				'category_name'		=> $category,
				'show_private'		=> $is_student
			), Config::POST_DISPLAYED, $offset));
			
		}else if($params['official'] == '1'){
			$this->set('posts', $this->model->getPosts(array(
				'restricted'	=> true,
				'official'		=> true,
				'category_name'	=> $category,
				'show_private'	=> $is_student
			), Config::POST_DISPLAYED, $offset));
			
		}else if(!$is_logged){
			throw new Exception('You must be logged');
		}
	}
	
	
	/*
	 * Show a post
	 */
	public function view($params){
		$this->setView('view.php');
		
		$is_logged = isset(User_Model::$auth_data);
		$is_student = $is_logged && isset(User_Model::$auth_data['student_number']);
		$is_admin = $is_logged && User_Model::$auth_data['admin']=='1';
		
		try {
			$post = $this->model->getPost((int) $params['id']);
			if(!$is_logged && $post['official'] == '0')
				throw new Exception('You must be logged');
			if(!$is_student && $post['private'] == '1')
				throw new Exception('You must be a student');
		}catch(Exception $e){
			throw new ActionException('Page', 'error404');
		}
		
		$this->set(array(
			'is_logged'		=> $is_logged,
			'is_student'	=> $is_student,
			'is_admin'		=> $is_admin,
			'groups_auth'	=> $is_logged ? Group_Model::getAuth() : array(),
			'post'			=> $post,
			'one_post'		=> true
		));
		
		if($is_logged)
			$this->set(array(
				'username'		=> User_Model::$auth_data['username']
			));
			
		if($is_student)
			$this->set(array(
				'firstname'		=> User_Model::$auth_data['firstname'],
				'lastname'		=> User_Model::$auth_data['lastname'],
				'avatar_url'	=> User_Model::$auth_data['avatar_url']
			));
		
		if($post['attachments_nb_photos'] != 0){
			$photos = array();
			foreach($post['attachments'] as $attachment){
				if(in_array($attachment['ext'], array('jpg', 'png', 'gif')))
					$photos[] = array(
						'id'	=> (int) $attachment['id'],
						'url'	=> $attachment['url']
					);
					if($post['category_id']==1){	
						$galleria[] = array(
							'thumb'	=> $attachment['thumb'],
							'image'	=> $attachment['url'],
							'id'	=> (int) $attachment['id'],
						);
					}
			}
			$this->addJSCode('Post.photos = '.json_encode($photos).';Post.photoDelete();');
			
			if($post['category_id']==1){		
				$this->addJSCode('Post.initGalleria('.json_encode($galleria).');');
			}
		}
		
	}
	
	
	/*
	 * Show a post with events of a day
	 */
	public function events($params){
		$this->setView('events.php');
		
		$is_logged = isset(User_Model::$auth_data);
		$is_student = $is_logged && isset(User_Model::$auth_data['student_number']);
		$is_admin = $is_logged && User_Model::$auth_data['admin']=='1';
		
		// Group
		if(isset($params['group'])){
			try {
				$group_model = new Group_Model();
				$group = $group_model->getInfoByName($params['group']);
				$this->set('group', $group);
				
			}catch(Exception $e){
				throw new ActionException('Page', 'error404');
			}
		}
		
		$year = (int) $params['year'];
		$month = (int) $params['month'];
		$day = isset($params['day']) ? (int) $params['day'] : null;
		
		$event_model = new Event_Model();
		$events = $event_model->getByMonth($year, $month, array(
			'group_id'	=> isset($group) ? $group['id'] : null,
			'official'			=> $is_logged ? null : true,
			'show_private'		=> $is_student
		));
		
		$post_ids = array();
		if(isset($day)){
			$day_time = mktime(0, 0, 0, $month, $day, $year);
			for($j = 0; $j < count($events); $j++){
				$event = & $events[$j];
				if(($event['date_start'] >= $day_time && $event['date_start'] <= $day_time+86400-1)
					|| ($event['date_end'] >= $day_time && $event['date_end'] <= $day_time+86400-1 && !(date('H', $event['date_end']) < 12 && date('Y-m-d', $event['date_end']) !=date('Y-m-d', $event['date_start'])))){
					$post_ids[] = (int) $event['post_id'];
				}
			}
		}else{
			foreach($events as &$event)
				$post_ids[] = (int) $event['post_id'];
		}
		
		$this->setTitle(
			(isset($group) ? $group['name'].' - ' : '').
			(isset($day_time) ? Date::dateMonth($day_time) : Date::getMonthByNum($month).' '.$year)
		);
		
		$this->set(array(
			'is_logged'		=> $is_logged,
			'is_student'	=> $is_student,
			'is_admin'		=> $is_admin,
			'groups_auth'	=> $is_logged ? Group_Model::getAuth() : array(),
			'posts'			=> count($post_ids)==0 ? array() : $this->model->getPosts(array(
				'restricted'		=> true,
				'show_private'		=> $is_student,
				'ids'				=> $post_ids
			), 1000, 0),
			'events' 			=> $events,
			'calendar_month'	=> $month,
			'calendar_year'		=> $year,
			'day_time'			=> isset($day_time) ? $day_time : null
		));
		
		if($is_logged)
			$this->set(array(
				'username'		=> User_Model::$auth_data['username']
			));
			
		if($is_student)
			$this->set(array(
				'firstname'		=> User_Model::$auth_data['firstname'],
				'lastname'		=> User_Model::$auth_data['lastname'],
				'avatar_url'	=> User_Model::$auth_data['avatar_url']
			));
		
	}
	
	
	
	/**
	 * Add a post
	 */
	public function iframe_add(){
		$this->setView('iframe_add.php');
		
		@set_time_limit(0);
		
		$uploaded_files = array();
		try {
			if(!isset(User_Model::$auth_data))
				throw new Exception(__('POST_ADD_ERROR_SESSION_EXPIRED'));
			$is_student = isset(User_Model::$auth_data['student_number']);
			
			// Message
			$message = isset($_POST['message']) ? trim($_POST['message']) : '';
			if($message == '' || $message == __('PUBLISH_DEFAULT_MESSAGE'))
				throw new Exception(__('POST_ADD_ERROR_NO_MESSAGE'));
			$message = preg_replace('#\n{2,}#', "\n\n", $message);
			
			// Category
			if(!isset($_POST['category']) || !ctype_digit($_POST['category']))
				throw new Exception(__('POST_ADD_ERROR_NO_CATEGORY'));
			$category = (int) $_POST['category'];
			
			// Official post (in a group)
			$official = isset($_POST['official']);
			
			// Group
			$group = isset($_POST['group']) && ctype_digit($_POST['group']) ? (int) $_POST['group'] : 0;
			if($group == 0){
				$group = null;
				$official = false;
			}else{
				$groups_auth = Group_Model::getAuth();
				if(isset($groups_auth[$group])){
					if($official && !$groups_auth[$group]['admin'])
						throw new Exception(__('POST_ADD_ERROR_OFFICIAL'));
				}else{
					throw new Exception(__('POST_ADD_ERROR_GROUP_NOT_FOUND'));
				}
			}
			
			// Private message
			$private = isset($_POST['private']);
			if($private && !$is_student)
				throw new Exception(__('POST_ADD_ERROR_PRIVATE'));
                        $dislike = isset($_POST['dislike']);
			
			
			$attachments = array();
			
			// Photos
			if(isset($_FILES['attachment_photo']) && is_array($_FILES['attachment_photo']['name'])){
				foreach($_FILES['attachment_photo']['size'] as $size){
					if($size > Config::UPLOAD_MAX_SIZE_PHOTO)
						throw new Exception(__('POST_ADD_ERROR_PHOTO_SIZE', array('size' => File::humanReadableSize(Config::UPLOAD_MAX_SIZE_PHOTO))));
				}
				if($filepaths = File::upload('attachment_photo')){
					foreach($filepaths as $filepath)
						$uploaded_files[] = $filepath;
					foreach($filepaths as $i => $filepath){
						$name = isset($_FILES['attachment_photo']['name'][$i]) ? $_FILES['attachment_photo']['name'][$i] : '';
						try {
							$img = new Image();
							$img->load($filepath);
							$type = $img->getType();
							if($type==IMAGETYPE_JPEG)
								$ext = 'jpg';
							else if($type==IMAGETYPE_GIF)
								$ext = 'gif';
							else if($type==IMAGETYPE_PNG)
								$ext = 'png';
							else
								throw new Exception();
							
							if($img->getWidth() > 800)
								$img->setWidth(800, true);
							$img->save($filepath);
							
							// Thumb
							$thumbpath = $filepath.'.thumb';
							$img->thumb(Config::$THUMBS_SIZES[0], Config::$THUMBS_SIZES[1]);
							$img->setType(IMAGETYPE_JPEG);
							$img->save($thumbpath);
							// Thumb
							$mobilepath = $filepath.'.mobile';
							$img->thumb(Config::$MOBILE_SIZES[0], Config::$MOBILE_SIZES[1]);
							$img->setType(IMAGETYPE_JPEG);
							$img->save($mobilepath);
							
							unset($img);
							$attachments[] = array($filepath, $name, $thumbpath,$mobilepath);
							$uploaded_files[] = $thumbpath;
							
						}catch(Exception $e){
							throw new Exception(__('POST_ADD_ERROR_PHOTO_FORMAT'));
						}
					}
				}
			}
			
			// Vid�os
			/* @uses PHPVideoToolkit : http://code.google.com/p/phpvideotoolkit/
			 * @requires ffmpeg, php5-ffmpeg
			 */
			if(isset($_FILES['attachment_video']) && is_array($_FILES['attachment_video']['name'])){
				foreach($_FILES['attachment_video']['size'] as $size){
					if($size > Config::UPLOAD_MAX_SIZE_VIDEO)
						throw new Exception(__('POST_ADD_ERROR_VIDEO_SIZE', array('size' => File::humanReadableSize(Config::UPLOAD_MAX_SIZE_VIDEO))));
				}
				if($filepaths = File::upload('attachment_video')){
					foreach($filepaths as $filepath)
						$uploaded_files[] = $filepath;
					foreach($filepaths as $i => $filepath){
						$name = isset($_FILES['attachment_video']['name'][$i]) ? $_FILES['attachment_video']['name'][$i] : '';
						
						try {
							/*require_once(APP_DIR."classes/class.Ffprobe_info.php");
							$info=new ffprobe($filepath);*/
							$format=array("avi","mp4");
							//$fileformat=explode(",",$info->format->format_name);
							$ext=pathinfo(File::getName($filepath), PATHINFO_EXTENSION);
							if(/*count(array_intersect($fileformat,$format))==0 ||*/ !in_array($ext,$format)){
								throw new Exception();
							}
							
						}catch(Exception $e){
						echo $e;
							throw new Exception(__('POST_ADD_ERROR_VIDEO_FORMAT'));
						}
						
						try {	
							//thumbnail
							$thumbpath = DATA_DIR.Config::DIR_DATA_TMP.File::getName($filepath).'.thumb.jpg';
							exec(PHPVIDEOTOOLKIT_FFMPEG_BINARY." -i ".escapeshellarg($filepath)." -deinterlace -an -ss 3 -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg -s 512x288 ".escapeshellarg($thumbpath)." 2>&1");
							// Video conversion
							$tempfilepath=DATA_DIR.Config::DIR_DATA_TMP.File::getName($filepath).".mp4";
							$command=PHPVIDEOTOOLKIT_FFMPEG_BINARY.' -i '.escapeshellarg($filepath).' -vcodec libx264 -profile high -preset ultrafast -r 25 -vb 1500k -maxrate 1500k -bufsize 1500k -vf scale="min(1280\, iw):-1" -threads 0 -acodec libfaac -ab 128k -y '.escapeshellarg($tempfilepath);
							exec($command);
							unlink($filepath);
							//permet de d�placer les infos au d�but pour les players flash
							exec('qt-faststart '.escapeshellarg($tempfilepath).' '.DATA_DIR.Config::DIR_DATA_TMP.'tempvideo.mp4');
							if(!is_file($tempfilepath)){
								unlink($thumbpath);
								throw new Exception();
							}
							unlink($tempfilepath);
							$filepath=DATA_DIR.Config::DIR_DATA_TMP.'tempvideo.mp4';

							$attachments[] = array($filepath, $name, $thumbpath);
							$uploaded_files[] = $filepath;
							
						}catch(Exception $e){
							throw new Exception(__('POST_ADD_ERROR_VIDEO_CONVERT').$e->getMessage());
						}
					}
				}
			}
			
			
			// Audios
			if(isset($_FILES['attachment_audio']) && is_array($_FILES['attachment_audio']['name'])){
				foreach($_FILES['attachment_audio']['size'] as $size){
					if($size > Config::UPLOAD_MAX_SIZE_AUDIO)
						throw new Exception(__('POST_ADD_ERROR_AUDIO_SIZE', array('size' => File::humanReadableSize(Config::UPLOAD_MAX_SIZE_AUDIO))));
				}
				if($filepaths = File::upload('attachment_audio')){
					foreach($filepaths as $filepath)
						$uploaded_files[] = $filepath;
					foreach($filepaths as $i => $filepath){
						if(!preg_match('#\.mp3$#', $filepath))
							throw new Exception(__('POST_ADD_ERROR_AUDIO_FORMAT'));
						
						$name = isset($_FILES['attachment_audio']['name'][$i]) ? $_FILES['attachment_audio']['name'][$i] : '';
						$attachments[] = array($filepath, $name);
					}
				}
			}
			
			
			// Files
			if(isset($_FILES['attachment_file']) && is_array($_FILES['attachment_file']['name'])){
				foreach($_FILES['attachment_file']['size'] as $size){
					if($size > Config::UPLOAD_MAX_SIZE_FILE)
						throw new Exception(__('POST_ADD_ERROR_FILE_SIZE', array('size' => File::humanReadableSize(Config::UPLOAD_MAX_SIZE_FILE))));
				}
				if($filepaths = File::upload('attachment_file')){
					foreach($filepaths as $filepath)
						$uploaded_files[] = $filepath;
					foreach($filepaths as $i => $filepath){
						if(!preg_match('#\.[a-z0-9]{2,4}$#i', $filepath))
							throw new Exception(__('POST_ADD_ERROR_FILE_FORMAT'));
						if(preg_match('#\.(jpg|png|gif|mp3|flv)$#i', $filepath))
							throw new Exception(__('POST_ADD_ERROR_FILE_FORMAT2'));
						
						$name = isset($_FILES['attachment_file']['name'][$i]) ? $_FILES['attachment_file']['name'][$i] : '';
						$attachments[] = array($filepath, $name);
					}
				}
			}
			
			
			// Event
			if(isset($_POST['event_title']) && isset($_POST['event_start']) && isset($_POST['event_end'])){
				// Title
				$event_title = trim($_POST['event_title']);
				if($event_title == '')
					throw new Exception(__('POST_ADD_ERROR_EVENT_NO_TITLE'));
				
				// Dates
				if(!($event_start = strptime($_POST['event_start'], __('PUBLISH_EVENT_DATE_FORMAT'))))
					throw new Exception(__('POST_ADD_ERROR_EVENT_DATE'));
				if(!($event_end = strptime($_POST['event_end'], __('PUBLISH_EVENT_DATE_FORMAT'))))
					throw new Exception(__('POST_ADD_ERROR_EVENT_DATE'));
				
				$event_start = mktime($event_start['tm_hour'], $event_start['tm_min'], 0, $event_start['tm_mon']+1, $event_start['tm_mday'], $event_start['tm_year']+1900);
				$event_end = mktime($event_end['tm_hour'], $event_end['tm_min'], 0, $event_end['tm_mon']+1, $event_end['tm_mday'], $event_end['tm_year']+1900);
				
				if($event_start > $event_end)
					throw new Exception(__('POST_ADD_ERROR_EVENT_DATE_ORDER'));
				
				$event = array($event_title, $event_start, $event_end);
			}else{
				$event = null;
			}
			
			
			// Survey
			if(isset($_POST['survey_question']) && isset($_POST['survey_end']) && isset($_POST['survey_answer']) && is_array($_POST['survey_answer'])){
				// Question
				$survey_question = trim($_POST['survey_question']);
				if($survey_question == '')
					throw new Exception(__('POST_ADD_ERROR_SURVEY_NO_QUESTION'));
				
				// Date
				if(!($survey_end = strptime($_POST['survey_end'], __('PUBLISH_EVENT_DATE_FORMAT'))))
					throw new Exception(__('POST_ADD_ERROR_SURVEY_DATE'));
				
				$survey_end = mktime($survey_end['tm_hour'], $survey_end['tm_min'], 0, $survey_end['tm_mon']+1, $survey_end['tm_mday'], $survey_end['tm_year']+1900);
				
				// Multiple answers
				$survey_multiple = isset($_POST['survey_multiple']);
				
				// Answers
				$survey_answers = array();
				foreach($_POST['survey_answer'] as $survey_answer){
					$survey_answer = trim($survey_answer);
					if($survey_answer != '')
						$survey_answers[] = $survey_answer;
				}
				if(count($survey_answers) < 2)
					throw new Exception(__('POST_ADD_ERROR_SURVEY_ANSWERS'));
				
				$survey = array($survey_question, $survey_end, $survey_multiple, $survey_answers);
			}else{
				$survey = null;
			}
			
			
			// Creation of the post
			$id = $this->model->addPost((int) User_Model::$auth_data['id'], $message, $category, $group, $official, $private,$dislike);
			
			
			// Attach files
			foreach($attachments as $attachment)
				$this->model->attachFile($id, $attachment[0], $attachment[1], isset($attachment[2]) ? $attachment[2] : null, isset($attachment[3]) ? $attachment[3] : null);
			
			// Event
			if(isset($event))
				$this->model->attachEvent($id, $event[0], $event[1], $event[2]);
			
			// Survey
			if(isset($survey))
				$this->model->attachSurvey($id, $survey[0], $survey[1], $survey[2], $survey[3]);
			
			
			$this->addJSCode('
				parent.location = "'. Config::URL_ROOT.Routes::getPage('home') .'";
			');
			
			
		}catch(Exception $e){
			// Delete all uploading files in tmp
			foreach($uploaded_files as $uploaded_file)
				File::delete($uploaded_file);
			
			$this->addJSCode('
				with(parent){
					Post.errorForm('.json_encode($e->getMessage()).');
				}
			');
		}
	}
	
	
	/**
	 * Delete a post
	 */
	public function delete($params){
		$this->setView('delete.php');
		
		try {
			$post = $this->model->getRawPost((int) $params['id']);
			
			$is_logged = isset(User_Model::$auth_data);
			$is_admin = $is_logged && User_Model::$auth_data['admin']=='1';
			$groups_auth = $is_logged ? Group_Model::getAuth() : array();
			
			if(($is_logged && User_Model::$auth_data['id'] == $post['user_id'])
			|| $is_admin
			|| (isset($post['group_id']) && isset($groups_auth[(int) $post['group_id']])) && $groups_auth[(int) $post['group_id']]['admin']){
				
				$this->model->delete((int) $params['id']);
				$this->set('success', true);
				
			}else{
				$this->set('success', false);
			}
		}catch(Exception $e){
			// Post not found
			$this->set('success', true);
		}
	}
	
	/**
	 * Delete only one attachment
	 */
	public function deleteattachment($params){
		$this->setView('delete.php');
			$is_logged = isset(User_Model::$auth_data);
			$is_admin = $is_logged && User_Model::$auth_data['admin']=='1';

			if( $is_admin && $this->model->deleteattachment((int) $params['id'],(int) $params['post_id'])){	
				$this->set('success', true);		
			}else{
				$this->set('success', false);
			}

	}
	/* Add one or many attachment to a photo post */
	public function addAttachment($param){
		$this->setView('iframe_add.php');
		$is_logged = isset(User_Model::$auth_data);
		$is_admin = $is_logged && User_Model::$auth_data['admin']=='1';
		@set_time_limit(0);

		$uploaded_files = array();
		$attachments = array();
		try {
			if($is_admin && isset($param['id']) && isset($_FILES['attachment_photo']) && is_array($_FILES['attachment_photo']['name']) ){
				foreach($_FILES['attachment_photo']['size'] as $size){
					if($size > Config::UPLOAD_MAX_SIZE_PHOTO)
						throw new Exception(__('POST_ADD_ERROR_PHOTO_SIZE', array('size' => File::humanReadableSize(Config::UPLOAD_MAX_SIZE_PHOTO))));
				}
				if($filepaths = File::upload('attachment_photo')){
					foreach($filepaths as $filepath)
						$uploaded_files[] = $filepath;
					foreach($filepaths as $i => $filepath){
						$name = isset($_FILES['attachment_photo']['name'][$i]) ? $_FILES['attachment_photo']['name'][$i] : '';
						try {
							$img = new Image();
							$img->load($filepath);
							$type = $img->getType();
							if($type==IMAGETYPE_JPEG)
								$ext = 'jpg';
							else if($type==IMAGETYPE_GIF)
								$ext = 'gif';
							else if($type==IMAGETYPE_PNG)
								$ext = 'png';
							else
								throw new Exception();

							if($img->getWidth() > 800)
								$img->setWidth(800, true);
							$img->save($filepath);

							// Thumb
							$thumbpath = $filepath.'.thumb';
							$img->thumb(Config::$THUMBS_SIZES[0], Config::$THUMBS_SIZES[1]);
							$img->setType(IMAGETYPE_JPEG);
							$img->save($thumbpath);

							unset($img);
							$attachments[] = array($filepath, $name, $thumbpath);
							$uploaded_files[] = $thumbpath;

						}catch(Exception $e){
							throw new Exception(__('POST_ADD_ERROR_PHOTO_FORMAT'));
						}
					}
				}

				// Attach files
				foreach($attachments as $attachment)
					$this->model->attachFile($param['id'], $attachment[0], $attachment[1], isset($attachment[2]) ? $attachment[2] : null);


				$this->addJSCode('
						parent.location = "'. Config::URL_ROOT.Routes::getPage('post',array('id'=>$param['id'])).'";
					');
			}
			Post_Model::clearCache();
		}catch(Exception $e){
			// Delete all uploading files in tmp
			foreach($uploaded_files as $uploaded_file)
				File::delete($uploaded_file);

			$this->addJSCode('
				with(parent){
					Post.errorForm('.json_encode($e->getMessage()).');
				}
			');
		}

	}
	
}

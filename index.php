<?php

if (!isset($_REQUEST)) {
	return;
}

//Строка для подтверждения адреса сервера из настроек Callback API
$confirmation_token = file_get_contents(__DIR__."/init/confirm_token.txt");

//Ключ доступа сообщества
$token = file_get_contents(__DIR__."/init/token.txt");

//Получаем и декодируем уведомление
$data = json_decode(file_get_contents('php://input'));

//Проверяем, что находится в поле "type"
switch ($data->type) {
	//Если это уведомление для подтверждения адреса...
	case 'confirmation':
		//...отправляем строку для подтверждения
		echo $confirmation_token;
		break;

	//Если это уведомление для подтверждения адреса...
	case 'group_leave':
		//...отправляем строку для подтверждения
		$user_id = $data->object->user_id;
		$lastIDFileName = __DIR__."/lastID.txt";

		if (file_get_contents($lastIDFileName) === strval($user_id)) {
			$link_info = json_decode(file_get_contents("https://api.vk.com/method/photos.getOwnerCoverPhotoUploadServer?access_token={$token}&group_id=226121937&crop_x=0&crop_y=0&crop_x2=1200&crop_y2=477&v=5.199"));
			$responseLink = $link_info->response->upload_url;

			$cover_path = dirname(__FILE__).'/images/mainNone.png';
			$post_data = array('photo' => new CURLFile($cover_path, 'image/png', 'images/mainNone.png'));

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $responseLink);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			$result = json_decode(curl_exec($ch), true);

			$saving_response = file_get_contents("https://api.vk.com/method/photos.saveOwnerCoverPhoto?hash=".$result['hash']."&photo=".$result['photo']."&access_token=".$token."&v=5.199");
		}
		echo 'ok';
		break;

			//Если это уведомление для подтверждения адреса...
	case 'group_join':
		//...отправляем строку для подтверждения
		//...получаем id его автора
		$user_id = $data->object->user_id;
		$lastIDFileName = __DIR__."/lastID.txt";
		file_put_contents($lastIDFileName, '');
        file_put_contents($lastIDFileName, print_r($user_id, true), FILE_APPEND);
		try {
			$user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&fields=photo_200&access_token={$token}&v=5.199"));
			$group_info = json_decode(file_get_contents("https://api.vk.com/method/groups.getById?gid=226121937&fields=members_count&access_token={$token}&v=5.199"));

			$userCount = $group_info->response->groups[0]->members_count;

			$user_photo_ID = $user_info->response[0]->photo_200;
			$userFirstName = $user_info->response[0]->first_name;
			$userLastName = $user_info->response[0]->last_name;
			$userFullName = $userFirstName . " " . $userLastName;

			$file = file_get_contents($user_photo_ID);
  			file_put_contents("site.png", $file);

			$mainImagePath = __DIR__."/images/main.png";
			$fontPath = __DIR__."/Comic_CAT.otf";
			$thisMainImagePath = __DIR__."/images/main".rand(1,3).".png";
			
			$image = imagecreatefrompng($thisMainImagePath);
			$color = imagecolorallocate($image, 0, 0, 0);
			$text = "#" . $userCount . " " . $userFullName;
			$fontSize = 13;
			
			imagepng(imagecreatefromstring(file_get_contents("site.png")), "avatar.png");
			
			$avatarImage = imagecreatefrompng(__DIR__."/avatar.png");
	
			imagecopymerge($image, $avatarImage, 768, 170, 0, 0, 240, 240, 80);
			
			$firstXLimit = 780;
			$lastXLimit = 1000;
			$xLimit = $lastXLimit - $firstXLimit;
			$cardMiddleX = $firstXLimit + ($xLimit/2);
			$boxttf = imagettfbbox($fontSize, 0, $fontPath, $text);
			$textLong = $boxttf[2] - $boxttf[0];
	
			$textXPosition = $cardMiddleX - ($textLong/2);
			if ($textLong > $xLimit) {
				$textXPosition = $firstXLimit;
				$cutLastName = mb_substr($userFullName, 0, 1);
				$text = "#" . $userCount . " " . $userFirstName . " " . $cutLastName . ".";
				$boxttf = imagettfbbox($fontSize, 0, $fontPath, $text);
				$textLong = $boxttf[2] - $boxttf[0];
				$textXPosition = $cardMiddleX - ($textLong/2);
				if ($textLong > $xLimit) {
					$textXPosition = $firstXLimit;
				}
			}
			imagesetclip($image, $firstXLimit, 400, $lastXLimit, 460);
			imagettftext($image, $fontSize, 0, $textXPosition, 440, $color, $fontPath, $text);
	
			imagepng($image, __DIR__."/images/result.png");
			imagedestroy($image);
			imagedestroy($avatarImage);

			$link_info = json_decode(file_get_contents("https://api.vk.com/method/photos.getOwnerCoverPhotoUploadServer?access_token={$token}&group_id=226121937&crop_x=0&crop_y=0&crop_x2=1200&crop_y2=477&v=5.199"));
			$responseLink = $link_info->response->upload_url;

			writeLogFile($text, false);

			$cover_path = dirname(__FILE__).'/images/result.png';
			$post_data = array('photo' => new CURLFile($cover_path, 'image/png', 'images/result.png'));

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $responseLink);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			$result = json_decode(curl_exec($ch), true);

			$saving_response = file_get_contents("https://api.vk.com/method/photos.saveOwnerCoverPhoto?hash=".$result['hash']."&photo=".$result['photo']."&access_token=".$token."&v=5.199");
		} catch (\Throwable $th) {
			writeLogFile($user_info, false);
		}
		echo 'ok';
		break;


	//Если это уведомление о новом сообщении...
	case 'message_new':
		//запись лога
		
		//...получаем id его автора
		$user_id = $data->object->message->from_id;
		//затем с помощью users.get получаем данные об авторе

		try {
			$user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&access_token={$token}&v=5.199"));
			$russVideos = json_decode(file_get_contents("https://api.vk.com/method/video.get?album_id=226121937_1&access_token={$token}&v=5.199"));
		} catch (\Throwable $th) {
			writeLogFile($th, false);
		}
		writeLogFile($russVideo, false);
		$user_name = $user_info->response[0]->first_name;
		//ответ юзера
		$user_response = $data->object->message->text;
		//прикрепление по умолчаниюaudio_playlist-226121937_2audio_playlist-226121937_2
		$attachment = "";

		//ответное сообщение
		if ($user_response == "Начать") {
			$resp_message = "Привет, {$user_name}! Это бот 90-х. Пока что я умею только советовать музыку. Посоветовать что-то из дискотеки 90-х?";
		} elseif (preg_match("/([Пп]ривет.*)|([Зз]дравству).+/i", $user_response)) {
			$resp_message = "Привет, {$user_name}! Это бот 90-х. Пока что я умею только советовать музыку. Посоветовать что-то из дискотеки 90-х?";
		} elseif (preg_match("/([Пп]осовет.+)|([Дд]а)|([Хх]орошо)/i", $user_response)) {
			$resp_message = "Лови!";
			$attachment = "audio_playlist-226121937_2";
		} elseif (preg_match("/тест/i", $user_response)) {
			$resp_message = "Ок!";
		} else {
			//и извлекаем из ответа его имя
			writeLogFile($data, false);
			$resp_message = "Я тебя не понимаю! Пожалуйста, переформулируй своё предложение. Пока что я умею только советовать музыку. Посоветовать что-то из дискотеки 90-х?";
		}

		//С помощью messages.send отправляем ответное сообщение
		$request_params = array(
		'message' => $resp_message,
		'peer_id' => $user_id,
		'access_token' => $token,
		'v' => '5.199',
		'random_id' => '0',
		'attachment' => $attachment,
		);

		$get_params = http_build_query($request_params);

		file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);

		//Возвращаем "ok" серверу Callback API

		echo('ok');

		break; 

	default:
		$mainImagePath = __DIR__."/images/main.png";
		$fontPath = __DIR__."/Comic_CAT.otf";
		$thisMainImagePath = __DIR__."/images/main".rand(1,3).".png";

		$userFirstName = "ВинокурчанЕККККККККККККККККККККККККовский";
		$userLastName = "Винокурчановский";
		$userFullName = $userFirstName . " " . $userLastName;
		$userCount = 535;
		
		$image = imagecreatefrompng($thisMainImagePath);
		imagesavealpha($image, true);
		$color = imagecolorallocate($image, 0, 0, 0);
		$colorWhite = imagecolorallocate($image, 255, 255, 255);
		imagecolortransparent($image, $colorWhite); // задаем прозрачность для картинки
		$text = "#" . $userCount . " " . $userFullName;
		$fontSize = 13;
		
		imagepng(imagecreatefromstring(file_get_contents("site.png")), "avatar.png");
		
		$avatarImage = imagecreatefrompng(__DIR__."/avatar.png");

		imagecopymerge($image, $avatarImage, 768, 170, 0, 0, 240, 240, 80);
		
		$firstXLimit = 780;
		$lastXLimit = 1000;
		$xLimit = $lastXLimit - $firstXLimit;
		$cardMiddleX = $firstXLimit + ($xLimit/2);
		$boxttf = imagettfbbox($fontSize, 0, $fontPath, $text);
		$textLong = $boxttf[2] - $boxttf[0];

		$textXPosition = $cardMiddleX - ($textLong/2);
		if ($textLong > $xLimit) {
			$textXPosition = $firstXLimit;
			$cutLastName = mb_substr($userFullName, 0, 1);
			$text = "#" . $userCount . " " . $userFirstName . " " . $cutLastName . ".";
			$boxttf = imagettfbbox($fontSize, 0, $fontPath, $text);
			$textLong = $boxttf[2] - $boxttf[0];
			$textXPosition = $cardMiddleX - ($textLong/2);
			if ($textLong > $xLimit) {
				$textXPosition = $firstXLimit;
			}
		}
		imagesetclip($image, $firstXLimit, 400, $lastXLimit, 460);
		imagettftext($image, $fontSize, 0, $textXPosition, 440, $color, $fontPath, $text);


		header('Content-Type: image/png');
		imagepng($image);
		imagedestroy($image);
		imagedestroy($avatarImage);
}

//для записи логов недоработанных вопросов
function writeLogFile($string, $clear = false){
    $log_file_name = __DIR__."/message.txt";
    if($clear == false) {
		$now = date("Y-m-d H:i:s");
		file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND);
    }
    else {
		file_put_contents($log_file_name, '');
        file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND);
    }
}
?>
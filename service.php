<?php

use Apretaste\Challenges;
use Apretaste\Chats;
use Apretaste\Level;
use Apretaste\Notifications;
use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Framework\Core;
use Framework\Alert;
use Framework\Database;
use Framework\Images;
use Framework\Utils;

class Service
{
	/**
	 * user friends list
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo
	 */

	public function _main(Request $request, Response $response)
	{
		$data = $request->input->data;
		$needle = $data->username ?? $data->id ?? $data->userId ?? false;

		// chat with a user
		if ($needle) {
			$this->_chat($request, $response);
			return;
		}

		// get the list of open chats
		$friends = $request->person->getFriends();

		foreach ($friends as &$friend) {
			$user = Database::queryFirst("SELECT id, username, gender, avatar, avatarColor, online FROM person WHERE id='{$friend}' LIMIT 1");
			$friend = $user;

			// get the person's avatar
			$friend->avatar = $friend->avatar ?? ($friend->gender === 'F' ? 'chica' : 'hombre');

			// get the person's avatar color
			$friend->avatarColor = $friend->avatarColor ?? 'verde';
		}

		$response->setLayout('chats.ejs');
		$response->setTemplate('main.ejs', ['friends' => $friends, 'title' => 'Amigos']);
	}

	/**
	 * Get the list of open conversations
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @throws Exception
	 * @author salvipascual
	 */
	public function _open(Request $request, Response $response)
	{
		// get the list of people chatting with you
		$chats = Chats::open($request->person->id);

		// get content for the view
		$content = [
			'chats' => $chats,
			'myuser' => $request->person->id];

		// send data to the view
		$response->setCache('hour');
		$response->setTemplate('open.ejs', $content);
	}

	/**
	 * Show the list of users online
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 */
	public function _online(Request $request, Response $response)
	{
		// get users who are online
		$online = Chats::online($request->person->id, $request->person->getFriends());

		// send info to the view
		$response->setCache('hour');
		$response->setLayout('chats.ejs');
		$response->setTemplate('online.ejs', ['users' => $online, 'title' => 'Online']);
	}

	/**
	 * Display the list of users found
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _users(Request $request, Response $response)
	{
		// get data from the request 
		$username = str_replace('@', '', $request->input->data->username);
		$province = $request->input->data->province;
		$gender = $request->input->data->gender;
		$min_age = $request->input->data->min_age;
		$max_age = $request->input->data->max_age;
		$religion = $request->input->data->religion;

		// declare variables
		$tags = [];
		$where = "";

		// search only by @username
		if ($username) {
			$where = "AND username = '$username'";
			$tags[] = "@$username";
		} // if the @username was not passed
		else {
			if ($gender) {
				$tags[] = Core::$gender[$gender];
				$where .= "AND gender = '$gender' ";
			}

			if ($min_age) {
				$tags[] = "< $min_age años";
				$year = date('Y') - $min_age;
				$where .= "AND year_of_birth <= $year ";
			}

			if ($max_age) {
				$tags[] = "> $max_age años";
				$year = date('Y') - $max_age;
				$where .= "AND year_of_birth >= $year ";
			}

			if ($province) {
				$tags[] = Core::$provinces[$province];
				$where .= "AND province = '$province' ";
			}

			if ($religion) {
				$tags[] = Core::$religions[$religion];
				$where .= "AND religion = '$religion'";
			}
		}

		// search for users
		$users = Database::query("
			SELECT id, username, avatar, avatarColor, gender, online
			FROM person 
			WHERE active = 1 $where
			ORDER BY online DESC, last_access DESC
			LIMIT 24");

		// error if no users were found
		if (empty($users)) {
			return $response->setTemplate('message.ejs', [
				'header' => 'No hay usuarios',
				'icon' => 'sentiment_neutral',
				'text' => 'Lo sentimos, pero no encontramos ningún usuario con esa combinación de datos. Cambie los paramétros de búsqueda e intente nuevamente.',
				'button' => ['href' => 'CHAT SEARCH', 'caption' => 'Buscar']
			]);
		}

		// add max reach tag
		if (count($users) == 24) {
			$tags[] = "Primeros 24";
		}

		// get content for the view
		$content = [
			'users' => $users,
			'tags' => $tags];

		// send data to the view
		$response->setCache();
		$response->setTemplate('users.ejs', $content);
	}

	/**
	 * Get the list of conversations
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @throws Exception
	 * @author salvipascual
	 */
	public function _chat(Request $request, Response $response)
	{
		$data = $request->input->data;
		$needle = $data->username ?? $data->id ?? $data->userId ?? false;

		$user = $needle ? Person::find($needle) : false;
		// ensure a person Id is passed
		if (!$user) {
			$response->setCache();
			$response->setTemplate('message.ejs', [
				'header' => 'Usuario inexistente',
				'icon' => 'sentiment_neutral',
				'text' => 'Lo sentimos, el usuario que usted busca no existe. Puede que halla dejado de usar la app. Busque otro usuario y comience a chatear.',
				'button' => ['href' => 'CHAT SEARCH', 'caption' => 'Buscar']
			]);
			return;
		}

		if (!$request->person->isFriendOf($user->id)) {
			$response->setLayout('chats.ejs');
			$response->setTemplate('message.ejs', [
				'header' => 'Oops!',
				'icon' => 'sentiment_neutral',
				'text' => "Parece que tú y @{$user->username} aún no son amígos, envíale una solicitud desde su perfíl para poder chatear.",
				'button' => ['href' => 'PERFIL', 'caption' => 'Ir al perfíl', 'data' => "username: {$user->id}"]
			]);
			return;
		}

		// get and display messages
		$chats = Chats::conversation($request->person->id, $user->id);

		$images = [];
		$chatImgDir = SHARED_PUBLIC_PATH . '/content/chat';
		foreach ($chats as $chat) {
			if ($chat->image) {
				$chat->image .= '.jpg';
				$images[] = "$chatImgDir/{$chat->image}";
			}
		}

		// get content for the view
		$content = [
			'messages' => $chats,
			'username' => $user->username,
			'myAvatar' => $request->person->avatar,
			'myColor' => $request->person->avatarColor,
			'myGender' => $request->person->gender,
			'myUsername' => $request->person->username,
			'id' => $user->id,
			'online' => $user->isOnline,
			'gender' => $user->gender,
			'last' => date('d/m/Y h:i a', strtotime($user->lastAccess))
		];

		// send data to the view
		$response->setLayout('chats.ejs');
		$response->setTemplate('chat.ejs', $content, $images);
	}

	/**
	 * Borrar un chat del usuario
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _borrar(Request $request, Response $response)
	{
		$deleteType = $request->input->data->type;
		$idToHide = $request->input->data->id;

		if ($deleteType === 'chat') {
			Chats::hide($request->person->id, $idToHide);
		}

		if ($deleteType === 'message') {
			Chats::hideMessage($request->person->id, $idToHide);
		}
	}

	/**
	 * Create a new chat without sending any emails, useful for the API
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author salvipascual
	 */
	public function _escribir(Request $request, Response $response)
	{
		if (!isset($request->input->data->id)) {
			return;
		}

		$userTo = Person::find($request->input->data->id);
		if (!$userTo) {
			return;
		}

		$image = $request->input->data->image ?? false;
		$fileName = '';

		// get the image name and path
		if ($image) {
			$chatImgDir = SHARED_PUBLIC_PATH . '/content/chat';
			$fileName = Utils::randomHash();
			$filePath = "$chatImgDir/$fileName.jpg";

			// save the optimized image on the user folder
			file_put_contents($filePath, base64_decode($image));
			Images::optimize($filePath);
		}

		$blocks = Chats::isBlocked($request->person->id, $userTo->id);
		if ($blocks->blocked > 0 || $blocks->blockedByMe > 0) {
			$text = "Su mensaje para @{$userTo->username} no pudo ser entregado, es posible que usted haya sido bloqueado por esa persona.";
			Notifications::alert($request->person->id, $text, 'error', "{'command':'PERFIL', 'data':{'id':'{$userTo->id}'}");
			return;
		}

		// store the note in the database
		$message = Database::escape($request->input->data->message, 499);
		Database::query("INSERT INTO _note (from_user, to_user, `text`, image) VALUES ({$request->person->id},{$userTo->id},'$message', '$fileName')");

		// send notification for the app
		$text = "@{$request->person->username} le ha enviado una nota";
		Notifications::alert($userTo->id, $text, 'message', "{'command':'CHAT', 'data':{'id':'{$request->person->id}'}}");

		// complete challenge
		Challenges::complete("chat", $request->person->id);
	}
}

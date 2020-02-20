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

class Service
{
	/**
	 * redirect to chat or the list of users
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return
	 * @throws Alert
	 * @throws Exception
	 * @author salvipascual
	 */
	public function _main(Request $request, Response $response)
	{
		// get the list of open chats
		if (empty($request->input->data->userId)) {
			return $this->_open($request, $response);
		}

		// chat with a user
		return $this->_chat($request, $response);
	}

	/**
	 * Get the list of open conversations
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return
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
	 * Search for a user
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _search(Request $request, Response $response)
	{
		// get content for the view
		$content = [
			'gender' => Core::$gender, 
			'religions' => Core::$religions, 
			'provinces' => Core::$provinces]; 

		// send data to the view
		$response->setCache('year');
		$response->setTemplate('search.ejs', $content);
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
		$online = Chats::online($request->person->id);

		// send info to the view
		$response->setCache('hour');
		$response->setTemplate('online.ejs', ['users' => $online]);
	}

	/**
	 * Display the list of users found
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _users(Request $request, Response $response)
	{
		// get data from the request 
		$username = $request->input->data->username;
		$province = $request->input->data->province;
		$gender = $request->input->data->gender;
		$min_age = $request->input->data->min_age;
		$max_age = $request->input->data->max_age;
		$religion = $request->input->data->religion;

		// declare variables
		$tags = []; $where = "";

		// search only by @username
		if($username) {
			$where = "AND username = '$username'";
			$tags[] = "@$username";
		}
		// if the @username was not passed
		else {
			if($gender) {
				$tags[] = Core::$gender[$gender];
				$where .= "AND gender = '$gender' ";
			}

			if($min_age) {
				$tags[] = "< $min_age años";
				$year = date('Y') - $min_age;
				$where .= "AND year_of_birth <= $year ";
			}

			if($max_age) {
				$tags[] = "> $max_age años";
				$year = date('Y') - $max_age;
				$where .= "AND year_of_birth >= $year ";
			}

			if($province) {
				$tags[] = Core::$provinces[$province];
				$where .= "AND province = '$province' ";
			}

			if($religion) {
				$tags[] = Core::$religions[$religion]['name'];
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
		if(count($users) == 24) {
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
	 * @return
	 * @throws Alert
	 * @throws Exception
	 * @author salvipascual
	 */
	public function _chat(Request $request, Response $response)
	{
		// ensure a person Id is passed
		if (empty($request->input->data->userId)) {
			$response->setCache();
			return $response->setTemplate('message.ejs', [
				'header' => 'Usuario inexistente',
				'icon' => 'sentiment_neutral',
				'text' => 'Lo sentimos, el usuario que usted busca no existe. Puede que halla dejado de usar la app. Busque otro usuario y comience a chatear.',
				'button' => ['href' => 'CHAT SEARCH', 'caption' => 'Buscar']
			]);
		}

		// get the username of the note
		$user = Person::find($request->input->data->userId);

		// get and display messages
		$chats = Chats::conversation($request->person->id, $user->id);

		// get content for the view
		$content = [
			'messages' => $chats,
			'username' => $user->username,
			'myuser' => $request->person->id,
			'id' => $user->id,
			'online' => $user->isOnline,
			'gender' => $user->gender,
			'last' => date('d/m/Y h:i a', strtotime($user->lastAccess))
		];

		// send data to the view
		$response->setTemplate('chat.ejs', $content);
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
		die("nothing");
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

		$blocks = Chats::isBlocked($request->person->id, $userTo->id);
		if ($blocks->blocked > 0 || $blocks->blockedByMe > 0) {
			$text = "Su mensaje para @{$userTo->username} no pudo ser entregado, es posible que usted haya sido bloqueado por esa persona.";
			Notifications::alert($request->person->id, $text, 'error', "{'command':'PERFIL', 'data':{'id':'{$userTo->id}'}");
			return;
		}

		// store the note in the database
		$message = Database::escape($request->input->data->message, 499);
		Database::query("INSERT INTO _note (from_user, to_user, `text`) VALUES ({$request->person->id},{$userTo->id},'$message')");

		// send notification for the app
		$text = "@{$request->person->username} le ha enviado una nota";
		Notifications::alert($userTo->id, $text, 'message', "{'command':'CHAT', 'data':{'id':'{$request->person->id}'}}");

		// complete challenge
		Challenges::complete("chat", $request->person->id);

		// add the experience
		Level::setExperience('START_CHAT_FIRST', $request->person->id, $userTo->username);
	}
}

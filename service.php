<?php

use Apretaste\Challenges;
use Apretaste\Chats;
use Apretaste\Level;
use Apretaste\Notifications;
use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Framework\Alert;
use Framework\Database;

class Service
{
	/**
	 * Get the list of conversations, or post a note
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
		if (isset($request->input->data->userId)) {
			// get the username of the note
			$user = Person::find($request->input->data->userId);

			// check if the username is valid
			if (!$user) {
				$response->setLayout('chat.ejs');
				return $response->setTemplate('notFound.ejs');
			}

			// get and display messages
			$chats = Chats::conversation($request->person->id, $user->id);

			$content = [
				'messages' => $chats,
				'username' => $user->username,
				'myuser' => $request->person->id,
				'id' => $user->id,
				'online' => $user->isOnline,
				'gender' => $user->gender,
				'last' => date('d/m/Y h:i a', strtotime($user->lastAccess))
			];

			$response->setLayout('chat.ejs');
			return $response->setTemplate('chat.ejs', $content);
		}

		// get the list of people chatting with you
		$chats = Chats::open($request->person->id);

		// send data to the view
		$response->setLayout('chat.ejs');
		$response->setTemplate('main.ejs', ['chats' => $chats, 'myuser' => $request->person->id]);
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
	 * Search an user by username
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return
	 * @throws Alert
	 * @author ricardo@apretaste.org
	 */
	public function _buscar(Request $request, Response $response)
	{
		$username = $request->input->data->username;
		$user = Person::find($username);
		if (!$user) {
			$response->setLayout('chat.ejs');
			return $response->setTemplate('notFound.ejs');
		}

		$request->input->data->userId = $user->id;
		$this->_main($request, $response);
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
		if (!isset($request->input->data->id)) return;

		$userTo = Person::find($request->input->data->id);
		if (!$userTo) return;

		$message = $request->input->data->message;

		$blocks = Chats::isBlocked($request->person->id, $userTo->id);
		if ($blocks->blocked > 0 || $blocks->blockedByMe > 0) {
			Notifications::alert(
				$request->person->id,
				"Su mensaje para @{$userTo->username} no pudo ser entregado, es posible que usted haya sido bloqueado por esa persona.",
				'error',
				"{'command':'PERFIL', 'data':{'id':'{$userTo->id}'}"
			);
			return;
		}

		// store the note in the database
		$message = Database::escape($message, 499);
		Database::query("INSERT INTO _note (from_user, to_user, `text`) VALUES ({$request->person->id},{$userTo->id},'$message')");

		// send notification for the app
		Notifications::alert(
			$userTo->id,
			"@{$request->person->username} le ha enviado una nota",
			'message',
			"{'command':'CHAT', 'data':{'id':'{$request->person->id}'}}"
		);

		// complete challenge
		Challenges::complete("chat", $request->person->id);

		// add the experience
		Level::setExperience('START_CHAT_FIRST', $request->person->id, $userTo->username);
	}

	/**
	 * Sub-service ONLINE
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 * @throws Alert
	 */
	public function _online(Request $request, Response $response)
	{
		$online = Chats::online($request->person->id);

		// send info to the view
		$response->setCache(5);
		$response->setLayout('chat.ejs');
		$response->setTemplate('online.ejs', ['users' => $online]);
	}
}

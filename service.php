<?php

class ChatService extends ApretasteService
{
	private $provinces = [
		'PINAR_DEL_RIO'       => 'Pinar del Rio',
		'LA_HABANA'           => 'La Habana',
		'ARTEMISA'            => 'Artemisa',
		'MAYABEQUE'           => 'Mayabeque',
		'MATANZAS'            => 'Matanzas',
		'VILLA_CLARA'         => 'Villa Clara',
		'CIENFUEGOS'          => 'Cienfuegos',
		'SANCTI_SPIRITUS'     => 'Sancti Spiritus',
		'CIEGO_DE_AVILA'      => 'Ciego de Avila',
		'CAMAGUEY'            => 'Camaguey',
		'LAS_TUNAS'           => 'Las Tunas',
		'HOLGUIN'             => 'Holguin',
		'GRANMA'              => 'Granma',
		'SANTIAGO_DE_CUBA'    => 'Santiago de Cuba',
		'GUANTANAMO'          => 'Guantanamo',
		'ISLA_DE_LA_JUVENTUD' => 'Isla de la Juventud',
		''                    => ''
	];

	/**
	 * Get the list of conversations, or post a note
	 *
	 * @throws \Exception
	 * @author salvipascual
	 */
	public function _main()
	{
		if (isset($this->request->input->data->userId)) {
			// get the username of the note
			$user = Utils::getPerson($this->request->input->data->userId);

			// check if the username is valid
			if (!$user) {
				$this->response->setLayout('chat.ejs');
				$this->response->setTemplate('notFound.ejs');

				return;
			}

			$messages = Social::chatConversation($this->request->person->id, $user->id);

			$chats = [];

			foreach ($messages as $message) {
				$chat = new stdClass();
				$chat->id = $message->note_id;
				$chat->username = $message->username;
				$chat->text = $message->text;
				$chat->sent = date_format(new DateTime($message->sent), 'd/m/Y h:i a');
				$chat->read = date('d/m/Y h:i a', strtotime($message->read));
				$chat->readed = $message->readed;
				$chats[] = $chat;
			}

			$content = [
				'messages'   => $chats,
				'username'   => $user->username,
				'myusername' => $this->request->person->username,
				'id'         => $user->id,
				'online'     => $user->online,
				'last'       => date('d/m/Y h:i a', strtotime($user->last_access))
			];

			$this->response->setLayout('chat.ejs');
			$this->response->setTemplate('chat.ejs', $content);

			return;
		}

		// get the list of people chatting with you
		$chats = Social::chatsOpen($this->request->person->id);
		$this->response->setLayout('chat.ejs');
		$this->response->setTemplate('main.ejs', ['chats' => $chats, 'myusername' => $this->request->person->username]);
	}

	/**
	 *
	 * @param Request
	 * @param Response
	 *
	 * @author ricardo@apretaste.org
	 */
	public function _borrar()
	{
		$deleteType = $this->request->input->data->type;
		$idToHide = $this->request->input->data->id;

		if ($deleteType === 'chat') {
			Social::chatHide($this->request->person->id, $idToHide);
		}
		if ($deleteType === 'message') {
			Social::chatMessageHide($this->request->person->id, $idToHide);
		}
	}

	/**
	 * Search an user by username
	 *
	 * @throws \Exception
	 * @author ricardo@apretaste.org
	 */

	public function _buscar()
	{
		$username = $this->request->input->data->username;
		$user = Utils::getPerson($username);
		if (!$user) {
			$this->response->setLayout('chat.ejs');
			$this->response->setTemplate('notFound.ejs');

			return;
		}

		$this->request->input->data->userId = $user->id;
		$this->_main();
	}

	/**
	 * Create a new chat without sending any emails, useful for the API
	 *
	 * @param Request
	 * @param Response
	 *
	 * @author salvipascual
	 */
	public function _escribir(Request $request, Response &$response)
	{
		if (!isset($this->request->input->data->id)) {
			return;
		}
		$userTo = Utils::getPerson($this->request->input->data->id);
		if (!$userTo) {
			return;
		}
		$message = $this->request->input->data->message;

		$blocks = Social::isBlocked($this->request->person->id, $userTo->id);
		if ($blocks->blocked > 0 || $blocks->blockedByMe > 0) {
			Utils::addNotification(
				$this->request->person->id,
				"Su mensaje para @{$userTo->username} no pudo ser entregado, es posible que usted haya sido bloqueado por esa persona.",
				"{'command':'PERFIL', 'data':{'id':'{$userTo->id}'}",
				'error'
			);

			return;
		}

		// store the note in the database
		$message = e($message, 499);
		q("INSERT INTO _note (from_user, to_user, `text`) VALUES ({$this->request->person->id},{$userTo->id},'$message')");

		// send notification for the app
		Utils::addNotification(
			$userTo->id,
			"@{$this->request->person->username} le ha enviado una nota",
			"{'command':'CHAT', 'data':{'id':'{$this->request->person->id}'}}",
			'message'
		);

		Challenges::complete("chat", $this->request->person->id);
	}

	/**
	 * Sub-service ONLINE
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function _online()
	{
		// get online users
		$users = q("
			SELECT *
			FROM person
			WHERE online = 1
			AND blocked = 0
			AND id <> '{$this->request->person->id}'
			ORDER BY last_access DESC
			LIMIT 20");

		// format users
		$online = [];
		foreach ($users as $user) {
			$profile = Social::prepareUserProfile($user);
			$online[] = [
				'id'       => $profile->id,
				'username' => $profile->username,
				'age'      => $profile->age,
				'province' => $this->provinces[$profile->province],
				'gender'   => $profile->gender
			];
		}

		// send info to the view
		$this->response->setCache(5);
		$this->response->setLayout('chat.ejs');
		$this->response->setTemplate('online.ejs', ['users' => $online]);
	}
}

<?php
require_once 'class_db.php';
require_once 'sanitize.php';
require_once 'class_user.php';

class Tutor81Messages{

	var $db_conn;
	
	public function __construct(){
		$this->db_conn = new MySQLConn();
	}
	
	public function getNumMessageToRead($user_id){
		$user_id = sanitize($user_id, INT);
		$query = "SELECT COUNT(id_recipient) as qta FROM recipients WHERE user_id = $user_id AND reading_date IS NULL";
		$res = $this->db_conn->query($query);
		return $res[0]['qta'] ? : false;
	}
	
	/**
	 * Restituisce la lista di tutti i messaggi destinati all'utente con <code>$user_id</code>
	 * 
	 * @param Integer $user_id
	 * @return array/false
	 */
	public function getListMessagesByRecipient($user_id){
		$user_id = sanitize($user_id, INT);
		$query = "SELECT
								messages.*,
								recipients.*,
								users.name as from_name,
								users.surname as from_surname,
								users.email as from_email,
								users.role as frome_role,
								users.company_id as from_company_id
							FROM recipients
								JOIN messages ON recipients.message_id = messages.id_message
								JOIN users ON messages.from_user_id = users.id
							WHERE recipients.user_id = $user_id
							ORDER BY messages.creation_date DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res : false;
	}
	
	/**
	 * Restituisce la lista di tutti i ticker destinati all'utente con <code>$user_id</code>
	 * 
	 * @param Integer $user_id
	 * @return array/false
	 */
	public function getListTickerByUser($user_id, $in_news_ticker = 1){
		$user_id = sanitize($user_id, INT);
		$in_news_ticker = sanitize($in_news_ticker, INT);
		$query = "SELECT company_id FROM users WHERE id = $user_id";
		$company_id = $this->db_conn->query($query);
		$query = "SELECT
								messages.*,
								recipients.*,
								users.name as from_name,
								users.surname as from_surname,
								users.email as from_email,
								users.role as frome_role,
								users.company_id as from_company_id
							FROM recipients
								JOIN messages ON recipients.message_id = messages.id_message
								JOIN users ON messages.from_user_id = users.id
							WHERE recipients.comany_id = $company_id AND messages.in_news_ticker = $in_news_ticker
							ORDER BY messages.creation_date DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res : false;
	}
	
	/**
	 * Restituisce la lista di tutti i ticker destinati all'utente con <code>$user_id</code>
	 * 
	 * @param Integer $user_id
	 * @return array/false
	 */
	public function getListTickerByCompany($company_id, $in_news_ticker = 1){
		$company_id = sanitize($company_id, INT);
		$in_news_ticker = sanitize($in_news_ticker, INT);
		$query = "SELECT
								messages.*,
								recipients.*,
								users.name as from_name,
								users.surname as from_surname,
								users.email as from_email,
								users.role as frome_role,
								users.company_id as from_company_id
							FROM recipients
								JOIN messages ON recipients.message_id = messages.id_message
								JOIN users ON messages.from_user_id = users.id
							WHERE recipients.comany_id = $company_id AND messages.in_news_ticker = $in_news_ticker
							ORDER BY messages.creation_date DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res : false;
	}
	
	/**
	 * Restituisce la lista di tutti i messaggi inviati da <code>$from_user_id</code>
	 * compresi i dati degli utenti e delle aziende che li hanno ricevuti
	 * 
	 * @param Integer $from_user_id
	 * @return Array associative and multi-dimensional
	 * 	array( array (messages.*,'recipients' => array(recipients.*, 
	 * 																								'user_surname' => String, 
	 * 																								'user_name' => String, 
	 * 																								'user_email' => String, 
	 * 																								'user_company_id' => Integer, 
	 * 																								'company_business_name' => String) 
	 * | false
	 */
	public function getListMessaggesBySender($from_user_id){
		$from_user_id = sanitize($from_user_id, INT);
		$query = "SELECT * FROM messages 
							WHERE from_user_id = $from_user_id 
							ORDER BY messages.creation_date DESC";
		$messages = $this->db_conn->query($query);
		if (isset($messages[0])){
			for ($i = 0; $i < count($messages); $i++){
				$query = "SELECT
										recipients.*,
										users.name as user_name,
										users.surname as _user_surname,
										users.email as user_email,
										users.company_id as user_company_id,
										companies.business_name as company_business_name
									FROM recipients 
										JOIN users ON recipients.user_id = users.id
										JOIN companies ON users.company_id = companies.id
									WHERE recipients.message_id = 	{$messages[$i]['id_message']}
									ORDER BY companies.business_name, users.surname, users.name";
				$recipients = $this->db_conn->query($query);
				if(isset($recipients[0])) $recipients;
				else unset($messages[$i]);
			}
			return isset($messages[0]) ? $messages : false;
		} else {
			return false;
		}
	}
	
	/**
	 * Restituisce la lista di tutti i ticker inviati da <code>$from_user_id</code>
	 * compresi i dati delle aziende che li hanno ricevuti
	 * 
	 * @param Integer $from_user_id
	 * @return Array associative and multi-dimensional
	 * 	array( array (messages.*,'recipients' => array(recipients.*, 'company_business_name' => String) | false
	 */
	public function getListTickerBySender($from_user_id){
		$from_user_id = sanitize($from_user_id, INT);
		$query = "SELECT * FROM messages 
							WHERE from_user_id = $from_user_id 
							ORDER BY messages.creation_date DESC";
		$tickers = $this->db_conn->query($query);
		if (isset($tickers[0])){
			for ($i = 0; $i < count($tickers); $i++){
				$query = "SELECT 
										recipients.*,
										companies.business_name as company_business_name
									FROM recipients
										JOIN companies ON recipients.company_id = companies.id
									WHERE recipients.message_id = {$tickers[$i]['id_message']}
									ORDER BY companies.business_name";
				$recipients = $this->db_conn->query($query);
				if (isset($recipients[0])) $tickers[$i]['recipients'] = $recipients;
				else unset($tickers[$i]);
			}
			return isset($tickers[0]) ? $tickers : false;
		} else {
			return false;
		}
	}
	
	/**
	 * Restituisce la lista di tutti gli oggetti dei messaggi inviati da <code>$from_user_id</code>
	 * compresi i dati sulla lettura
	 * 
	 * @param Integer $from_user_id
	 * @return Array | false
	 */
	public function getListObjectMessaggesBySender($from_user_id){
		$from_user_id = sanitize($from_user_id, INT);
		$query = "SELECT
								messages.id_message,
								messages.object,
								messages.creation_date,
								messages.last_update,
								COUNT(recipients.user_id) as num_users,
								COUNT(IF(recipients.reading_date IS NULL, NULL, 1)) as num_read
							FROM messages 
								JOIN recipients ON messages.id_message = recipients.message_id
							WHERE messages.from_user_id = $from_user_id AND recipients.user_id <> 0
							GROUP BY messages.id_message
							ORDER BY messages.creation_date DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]['id_message']) ? $res : false;
	}
	
	/**
	 * Restituisce la lista di tutti gli oggetti dei messaggi inviati a <code>$user_id</code>
	 * 
	 * @param Integer $user_id
	 * @return Array | false
	 */
	public function getListObjectMessaggesByRecipient($user_id){
		$user_id = sanitize($user_id, INT);
		$query = "SELECT *
							FROM messages 
								JOIN recipients ON messages.id_message = recipients.message_id
							WHERE recipients.user_id = $user_id
							ORDER BY messages.creation_date DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]['id_message']) ? $res : false;
	}
	
	/**
	 * Restituisce la lista di tutti gli oggetti dei ticker pubblicati da <code>$from_user_id</code>
	 * 
	 * @param Integer $from_user_id
	 * @return Array | false
	 */
	public function getListObjectTickersBySender($from_user_id){
		$from_user_id = sanitize($from_user_id, INT);
		$query = "SELECT
								messages.id_message,
								messages.object,
								messages.creation_date,
								messages.last_update,
								messages.in_news_ticker
							FROM messages 
								JOIN recipients ON messages.id_message = recipients.message_id
							WHERE messages.from_user_id = $from_user_id AND recipients.company_id <> 0
							ORDER BY messages.creation_date, messages.last_update DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res : false;
	}
	
	/**
	 * Restituisce la lista di tutti gli oggetti dei ticker inviati all'azienda <code>$company_id</code>
	 * 
	 * @param Integer $company_id
	 * @return Array | false
	 */
	public function getListObjectTickersByRecipient($company_id){
		$company_id = sanitize($company_id, INT);
		$query = "SELECT *
							FROM messages 
								JOIN recipients ON messages.id_message = recipients.message_id
							WHERE recipients.company_id = $company_id
							ORDER BY messages.creation_date DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]['id_message']) ? $res : false;
	}
	
	public function getMessageDetail($id_message){
		$id_message = sanitize($id_message, INT);
		$query = "SELECT messages.*
							FROM messages
							WHERE messages.id_message = $id_message";
		$message = $this->db_conn->query($query);
		if (isset($message[0])){
			$query = "SELECT
									recipients.*,
									users.name as user_name,
									users.surname as user_surname,
									users.email as user_email,
									users.company_id as user_company_id,
									companies.business_name as company_business_name
								FROM recipients 
									JOIN users ON recipients.user_id = users.id
									JOIN companies ON users.company_id = companies.id
								WHERE recipients.message_id = $id_message
								ORDER BY companies.business_name, users.surname, users.name";
				$message[0]['recipients'] = $this->db_conn->query($query);
				return $message[0];
		} else {
			return false;
		}
	}
	
	public function getTickerDetail($id_message){
		$id_message = sanitize($id_message, INT);
		$query = "SELECT messages.*
							FROM messages
							WHERE messages.id_message = $id_message";
		$ticker = $this->db_conn->query($query);
		if (isset($ticker[0])){
			$query = "SELECT
									recipients.*,
									companies.business_name as company_business_name
								FROM recipients
									JOIN companies ON recipients.company_id = companies.id
								WHERE recipients.message_id = $id_message
								ORDER BY companies.business_name";
				$ticker[0]['recipients'] = $this->db_conn->query($query);
				return $ticker[0];
		} else {
			return false;
		}
	}
	
	/**
	 * Aggiunge un nuovo messaggio/ticker alla tabella <code>messages</code> e
	 * aggiunge i destinatari del messaggio/ticker alla tabella <code>recipients</code>
	 * 
	 * @param Integer $from_user_id
	 * @param String (max 250 char) $object
	 * @param String (max 65535 char) $message_text
	 * @param array of Integer $to_users
	 * @param array of Integer $companies
	 * @return array associative multi-dimensional 
	 * 	of Integer array('message_id' => Integer, 'recipients' => array of Integer) / false
	 */
	public function addMessage($from_user_id, $object, $message_text,$to_users = array(),$companies = array(), $in_news_ticker = 0){
		$from_user_id = sanitize($from_user_id, INT);
		$object = $this->db_conn->escapestr($object);
		$message_text = $this->db_conn->escapestr($message_text);
		$in_news_ticker = sanitize($in_news_ticker, INT);
		for ($i = 0; $i < count($to_users); $i++){
			$to_users[$i] = sanitize($to_users[$i], INT);
		}
		for ($i = 0; $i < count($companies); $i++){
			$companies[$i] = sanitize($companies[$i], INT);
		}
		$creation_date = date('Y-m-d H:i:s');
		$query = "INSERT INTO messages (from_user_id,object,message_text,in_news_ticker,creation_date)
							VALUES ($from_user_id,'$object','$message_text',$in_news_ticker,'$creation_date')";
		$message_id = $this->db_conn->insert($query);
		$res = $message_id ? array('message_id' => $message_id,'recipients' => array()) : false;
		if ($message_id) {
			foreach ($to_users as $user_id){
				$query = "INSERT INTO recipients (message_id,user_id)  VALUES ($message_id,$user_id)";
				$recipient_id = $this->db_conn->insert($query);
				if ($recipient_id > 0) array_push($res['recipients'], $recipient_id);
			}
			foreach ($companies as $company_id){
				$query = "INSERT INTO recipients (message_id,company_id)  VALUES ($message_id,$company_id)";
				$recipient_id = $this->db_conn->insert($query);
				if ($recipient_id > 0) array_push($res['recipients'], $recipient_id);
			}
		}
		return isset($res['recipients']) && count($res['recipients']) > 0 ? $res : false;
	}
	
	public function setInNewsTicker($id_message, $in_news_ticker = 1){
		$id_message = sanitize($id_message, INT);
		$in_news_ticker = sanitize($in_news_ticker, INT);
		$query = "UPDATE messages SET in_news_ticker = $in_news_ticker WHERE id_message = $id_message";
		$res = $this->db_conn->update($query);
		return $res ? : false;
	}
	
	public function setMessageReadByRecipient($id_recipient){
		$id_recipient = sanitize($id_recipient, INT);
		$reading_date = date('Y-m-d H:i:s');
		$query = "UPDATE recipients SET reading_date = '$reading_date' WHERE id_recipient = $id_recipient";
		$res = $this->db_conn->update($query);
		return $res ? : false;
	}
		
}
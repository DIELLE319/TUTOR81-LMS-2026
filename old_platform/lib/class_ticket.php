<?php
require_once 'class_db.php';
require_once 'sanitize.php';

class Tutor81Ticket{

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}
	
	public function getTicketRequestDetail($id){
		$id = sanitize($id, INT);
		$query = "SELECT * FROM ticket_request WHERE id = ".$id;
		$res = $this->db_conn->query($query);
		return $res[0];
	}
	
	public function addTicketRequest($learning_project_user_id,$email,$problem,$message,$user_agent){
            $learning_project_user_id = filter_var($learning_project_user_id, FILTER_SANITIZE_NUMBER_INT);
            $email = $this->db_conn->escapestr(filter_var($email, FILTER_VALIDATE_EMAIL));
            $problem = $this->db_conn->escapestr(htmlentities($problem, ENT_QUOTES));
            $message = $this->db_conn->escapestr(htmlentities($message, ENT_QUOTES));
            $user_agent = $this->db_conn->escapestr($user_agent);
            $query = "INSERT INTO ticket_request(learning_project_user_id,email,problem,message,user_agent,creation_date) VALUES ("
                                    .$learning_project_user_id.",'".$email."','".$problem."','".$message."','".$user_agent."','".date("Y-m-d H:i:s")."')";
            $res = $this->db_conn->insert($query);
            return $res;
	}
	
	/**
	 * Restituisce tutti i ticket di tutti gli utenti in accordo con il parametro $closed
	 * $closed = 0 per tutti i ticklet aperti
	 * $closed = 1 per tutti i ticklet chiusi
	 * $closed = -1 per tutti i ticklet
	 * 
	 * @param number $closed 
	 * @return Ambigous <boolean, multitype:>
	 */
	public function getListTickets($closed = true){
            $closed = filter_var($closed, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $query = "SELECT tickets.*, 
                        users.name, 
                        users.surname, 
                        users.company_id, 
                        companies.business_name, 
                        learning_project_users.learning_project_id, 
                        learning_project.title 
                      FROM tickets 
                        LEFT JOIN ticket_topics ON ticket_topic_id = id_ticket_topic 
                        LEFT JOIN users ON tickets.user_id = users.id 
                        LEFT JOIN companies ON users.company_id = companies.id 
                        LEFT JOIN learning_project_users ON tickets.license_id = learning_project_users.id 
                        LEFT JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id 
                        WHERE closed = $closed";
            $res = $this->db_conn->query($query);
            return isset($res[0]) ? $res : false;
	}
	
	public function getTicketByCode($code, $closed = -1){
		$code = $this->db_conn->escapestr($code);
		$closed = sanitize($closed, INT);
		$and_closed = $closed >= 0 ? "AND closed = $closed" : "";
		$query = "SELECT * FROM tickets LEFT JOIN ticket_topics ON ticket_topic_id = id_ticket_topic WHERE code = '$code' $and_closed";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res[0] : false;
	}
	
	public function getTicketById($id, $closed = -1){
		$id = sanitize($id, INT);
		$closed = sanitize($closed, INT);
		$and_closed = $closed >= 0 ? "AND closed = $closed" : "";
		$query = "SELECT * FROM tickets LEFT JOIN ticket_topics ON ticket_topic_id = id_ticket_topic WHERE id_ticket = $id $and_closed";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res[0] : false;
	}
	
	public function getTicketByUserid($user_id, $closed = -1){
		$user_id = sanitize($user_id, INT);
		$closed = sanitize($closed, INT);
		$and_closed = $closed >= 0 ? "AND closed = $closed" : "";
		$query = "SELECT * FROM tickets LEFT JOIN ticket_topics ON ticket_topic_id = id_ticket_topic WHERE user_id = $user_id $and_closed ORDER BY creation_date_ticket DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res : false;
	}
	
	public function getTicketByStaffid($staff_id, $closed = -1){
		$staff_id = sanitize($staff_id, INT);
		$closed = sanitize($closed, INT);
		$and_closed = $closed >= 0 ? "AND closed = $closed" : "";
		$query = "SELECT * FROM tickets LEFT JOIN ticket_topics ON ticket_topic_id = id_ticket_topic WHERE staff_id = $staff_id $and_closed ORDER BY creation_date_ticket DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res : false;
	}
	
	public function getTicketThreads($ticket_id){
		$ticket_id = sanitize($ticket_id, INT);
		$query = "SELECT * FROM ticket_threads WHERE ticket_id = $ticket_id ORDER BY creation_date_thread ASC";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res : false;
	}
	
	public function getTicketTopics(){
		return $this->db_conn->query("SELECT * FROM ticket_topics WHERE suspended = 0");
	}
	
	public function addThread($ticket_id,$staff_id,$user_id,$thread_type,$source,$object,$body,$ip_address,$user_agent){
		$ticket_id = sanitize($ticket_id, INT);
		$staff_id = sanitize($staff_id, INT);
		$user_id = sanitize($user_id, INT);
		$thread_type = $this->db_conn->escapestr($thread_type);
		$source = $this->db_conn->escapestr($source);
		$object = $this->db_conn->escapestr(htmlentities($object, ENT_QUOTES));
		$body = $this->db_conn->escapestr(htmlentities($body, ENT_QUOTES));
		$ip_address = $this->db_conn->escapestr($ip_address);
		$user_agent = $this->db_conn->escapestr($user_agent);
		$creation_date_thread = date('Y-m-d H:i:s');
		$query = "INSERT INTO ticket_threads (ticket_id,staff_id,user_id,thread_type,source,object,body,ip_address,user_agent,creation_date_thread)
							VALUES ($ticket_id,$staff_id,$user_id,'$thread_type','$source','$object','$body','$ip_address','$user_agent','$creation_date_thread')";
		$res = $this->db_conn->insert($query);
		return $res ? : false; 
	}
	
	public function addTicket($user_id,$user_email,$license_id,$type_help,$ticket_topic_id,$staff_id,$source,$object,$body,$ip_address,$user_agent){
		$user_id = sanitize($user_id, INT);
		$user_email = $this->db_conn->escapestr($user_email);
		$type_help = $this->db_conn->escapestr($type_help);
		$ticket_topic_id = sanitize($ticket_topic_id, INT);
		$staff_id = sanitize($staff_id, INT);
		$code = uniqid('ticket.');
		$creation_date = date('Y-m-d H:i:s');
		$query = "INSERT INTO tickets (code,user_id,user_email,license_id,type_help,ticket_topic_id,staff_id,creation_date_ticket)
							VALUES ('$code',$user_id,'$user_email',$license_id,'$type_help',$ticket_topic_id,$staff_id,'$creation_date')";
		$ticket_id = $this->db_conn->insert($query);
		if ($ticket_id > 0) {
			$thread_type = 'M';
			$res = $this->addThread($ticket_id, $staff_id, $user_id, $thread_type, $source, $object, $body, $ip_address, $user_agent);
			return $res > 0 ? $ticket_id : false;
		}
		return false;
	}
	
	public function assignTicket($id_ticket, $staff_id){
		$id_ticket = sanitize($id_ticket, INT);
		$staff_id = sanitize($staff_id, INT);
		$query = "UPDATE tickets SET staff_id = $staff_id WHERE id_ticket = $id_ticket";
		$res = $this->db_conn->update($query);
		return $res > 0 ? $res : false;
	}
	
	public function setTicketCloseOpen($id_ticket, $closed){
		$id_ticket = sanitize($id_ticket, INT);
		$closed = sanitize($closed, INT);
		$query = "UPDATE tickets SET closed = $closed WHERE id_ticket = $id_ticket";
		$res = $this->db_conn->update($query);
		return $res > 0 ? $res : false;
	}
	
}
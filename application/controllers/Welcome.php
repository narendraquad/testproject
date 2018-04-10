<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->model('Users_model','user');
	}

	public function index(){
		$this->db->select('*');
		$this->db->from('users');
		$result = $this->db->get()->result();
		if ($result) {
			return $result;
		} else {
			return false;
		}
		//$this->load->view('users/list', $data);
	}

	public function add(){
		if (!empty($this->input->post())) {
		}
		$this->load->view('users/add');
	}

	public function update($id){
		if (!empty($this->input->post())) {
		}
		$this->load->view('users/update',$data);
	}

	public function delete($id){

	}
}
/* End of file Welcome.php */
/* Location: ./application/controllers/Welcome.php */
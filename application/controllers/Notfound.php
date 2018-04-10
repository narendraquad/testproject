<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notfound extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$this->load->view('404');
	}
	public function exceptionerrors($id)
	{	
		$ids = (array)json_decode(base64_decode($id));
		$data = array('ids' => $ids);
		$this->load->view('error_page', $data, FALSE);
	}

	/**
	 * [nopermissions description]
	 * @return [type] [description]
	 */
	public function nopermissions()
	{
		$this->load->view('no_permissions');
	}

	/**
	 * [stillnopermissions description]
	 * @return [type] [description]
	 */
	public function stillnopermissions()
	{
		$this->load->view('stillno_permissions');
	}
}

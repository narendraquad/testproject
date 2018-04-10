<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Users_model extends CI_Model {

	const TABLE_NAME = 'users';

	public function get($where) {
		$this->db->select('*');
		$this->db->from(self::TABLE_NAME);
		if (is_array($where)) {
			foreach ($where as $field=>$value) {
				$this->db->where($field, $value);
			}
		}
		$result = $this->db->get()->result();
		if ($result) {
			return $result;
		} else {
			return false;
		}
	}

	public function insert($data) {
		if ($this->db->insert(self::TABLE_NAME, $data)) {
			return $this->db->insert_id();
		} else {
			return false;
		}
	}

	public function update($data, $where) {
		$this->db->update(self::TABLE_NAME, $data, $where);
		return $this->db->affected_rows();
	}

	public function delete($where) {
		$this->db->delete(self::TABLE_NAME, $where);
		return $this->db->affected_rows();
	}
}
/* End of file Users_model.php */
/* Location: ./application/models/Users_model.php */
<?php



class EXT_Versioning
{
	//--------------------------------------------------------------------------

	public function __construct()
	{
		$this->db =& sys::$lib->db;
	}

	//--------------------------------------------------------------------------

	public function restore_all($version)
	{
		$total   = $this->db->where('version=?', $version)->count_all('versioning');
		$restore = 0;
		
		if ($total)
		{
			$rcount = 10;
			$steps  = ceil($total/$rcount);
			
			for ($i=0; $i<$steps; $i++)
			{
				$vdata = $this->db->where('version=?', $version)->order_by('id DESC')->limit($i*$rcount, $rcount)->get('versioning')->result();

				foreach ($vdata as $row)
				{
					$restore += $this->_restore(&$row);
				}
			}
		}

		return array(
			'restore' => $restore,
			'total'   => $total
		);
	}

	//--------------------------------------------------------------------------

	public function add_version($table, $pid, $data, $version_key = NULL)
	{
		$data      = serialize($data);
		$data_hash = md5($data);

		if ($version_key) $this->db->where('version=?', $version_key);
		$exists = $this->db->where('`table`=? AND pid=? AND data_hash=?', $table, $pid, $data_hash)->limit(1)->update('versioning', array('postdate'=>time()));

		if ( ! $exists)
		{
			$this->db->insert('versioning', array(
				'table'     => $table,
				'pid'       => $pid,
				'postdate'  => time(),
				'data'      => $data,
				'data_hash' => $data_hash,
				'version'   => $version_key
			));
		}

		return TRUE;
	}

	//--------------------------------------------------------------------------

	private function _restore(&$row)
	{
		if ( ! trim($row->data)) return FALSE;

		$row->data = @unserialize($row->data);
		if ($row->data)
		{
			return $this->db->where('id=?', $row->pid)->limit(1)->update($row->table, $row->data);
		}
		else
		{
			return FALSE;
		}
	}

	//--------------------------------------------------------------------------
}
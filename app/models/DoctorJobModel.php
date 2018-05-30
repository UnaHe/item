<?php

class DoctorJobModel extends ModelBase
{
    public function clientGetList($project_id){
        $tag = CacheBase::makeTag(self::class . 'clientGetList'.$project_id);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $result = $this->find(
                array(
                    'conditions' => 'project_id = ?1',
                    'bind' => array(1 => $project_id)
                )
            );
            if (CACHING) {
                $this->cache->save($tag, $result, 864000, null, array(
                    self::class . 'getList'
                ));
            }
        }
        return $result;
    }

    public function getDetailsByDoctorJobIdSimple($doctorJobId)
    {
        $tag = CacheBase::makeTag(self::class . 'getDetailsByDoctorJobIdSimple', $doctorJobId);
        $result = CACHING ? $this->cache->get($tag) : false;
        if (!$result) {
            $sql = 'SELECT * FROM '.$this->getSource().' WHERE doctor_job_id = ?';
//			echo $sql;die;
            $result = new Phalcon\Mvc\Model\Resultset\Simple (null, $this,
                $this->getReadConnection()->query($sql, [$doctorJobId]));
            if ($result->valid()) {
                $result = $result->toArray()[0];
            } else {
                $result = false;
            }
            if (CACHING) {
                $this->cache->save($tag, $result);
            }
        }
        return $result;
    }

	public function getSource()
	{
		return DB_PREFIX . 'doctor_job';
	}
}

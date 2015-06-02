<?php
App::uses('AppModel', 'Model');


class CrimeReports extends AppModel {
  public $name = 'CrimeReports';
  public $useTable = 'TPD_ONLINE_TBL';

#    $keywords = '%'.$this->params->query['q'].'%';
#    $conditions = array('OR' => array(
#                                  array("lower(CrimeReports.CRIME_TYP) LIKE lower('%$keywords%')"),
#                                  array("lower(CrimeReports.SUS_LAST_NAME) LIKE lower('%$keywords%')"),
#                                  array("TO_CHAR(CrimeReports.REPORT_DATE) LIKE '%$keywords%'"),
#                                  array("TO_CHAR(CrimeReports.REP_NUM) LIKE '%$keywords%'"),
#                                  array("lower(CrimeReports.VIC_LAST_NAME) LIKE lower('%$keywords%')"),
#                                  array("lower(CrimeReports.REP_LAST_NAME) LIKE lower('%$keywords%')"),
#                                  array("lower(CrimeReports.CRIME_NARR) LIKE lower('%$keywords%')")));
#    $this->T['crime_reports'] = $this->CrimeReports->find('all',
#      array('order' => 'rep_num DESC',
#            'limit' => '100',
#            'conditions' => $conditions));


  public $order = 'rep_num DESC';
  public $actsAs = array(
    'Search.Searchable',
  );

  public $filterArgs = array(
    array('name' => 'q', 'type' => 'query', 'method' => 'filterQuery'),
    array('name' => 'from', 'type' => 'query', 'method' => 'filterFrom'),
    array('name' => 'to', 'type' => 'query', 'method' => 'filterTo'),
  );

  public function filterQuery($data = array()) {
    if(empty($data['q'])) {
      return array();
    }
    $keywords = '%'.$data['q'].'%';
    return array('OR' => array(
                                  array("lower(CrimeReports.CRIME_TYP) LIKE lower('%$keywords%')"),
                                  array("lower(CrimeReports.SUS_LAST_NAME) LIKE lower('%$keywords%')"),
                                  array("TO_CHAR(CrimeReports.REPORT_DATE) LIKE '%$keywords%'"),
                                  array("TO_CHAR(CrimeReports.REP_NUM) LIKE '%$keywords%'"),
                                  array("lower(CrimeReports.VIC_LAST_NAME) LIKE lower('%$keywords%')"),
                                  array("lower(CrimeReports.REP_LAST_NAME) LIKE lower('%$keywords%')"),
                                  array("lower(CrimeReports.CRIME_NARR) LIKE lower('%$keywords%')")));
  }
  public function filterFrom($data = array()) {
    if(empty($data['from'])) {
      return array();
    }
    return array('CrimeReports.imp_date >= ' => $data['from']);
  }
  public function filterTo($data = array()) {
    if(empty($data['to'])) {
      return array();
    }
    return array('CrimeReports.imp_date <= ' => $data['to'].' 23:59:59');
  }

        //The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
#        public $belongsTo = array(
#                'Officer' => array(
#                        'className' => 'Officer',
#                        'foreignKey' => 'officer_id',
#/*                      'conditions' => '',
#                        'fields' => '',
#                        'order' => ''*/
#                )
#        );

}

?>

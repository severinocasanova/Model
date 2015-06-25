<?php
App::uses('AppModel', 'Model');

class CrimeReports extends AppModel {
  var $messages = array();
  #public $name = 'oracle_test';
  public $useDbConfig = 'oracle'; 
  public $useTable = 'TPD_ONLINE_TBL';
  public $primaryKey = 'rep_num';

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


  function add_crime_report($args){
    $hash = $args['hash'];
    $id = $args['id'];
    $hash['report_date'] = date('dmY');
    $hash['rep_num'] = $id;
    $hash['ip_addr'] = $_SERVER['REMOTE_ADDR'];
    if($hash['crime_from_time_ampm'] == 'pm') $hash['crime_from_time_hour'] += 12;
    $hash['crime_from_time'] = sprintf("%02d", $hash['crime_from_time_hour']).$hash['crime_from_time_min'];
    if($hash['crime_from_time'] == 0) $hash['crime_from_time'] = '';
    if($hash['crime_to_time_ampm'] == 'pm') $hash['crime_to_time_hour'] += 12;
    $hash['crime_to_time'] = sprintf("%02d", $hash['crime_to_time_hour']).$hash['crime_to_time_min'];
    if($hash['crime_to_time'] == 0) $hash['crime_to_time'] = '';
    #if($_POST['SUS_HEIGHT_IN'] < 10) $_POST['SUS_HEIGHT_IN'] = '0' . $_POST['SUS_HEIGHT_IN'];
    $hash['sus_height'] = ($hash['sus_height_in'] + $hash['sus_height_ft'] > 0 ? sprintf("%02d", $hash['sus_height_ft']).$hash['sus_height_in']:'');
    $phone_numbers = array('vic_home_phone','vic_work_phone','vic_cell_phone');
    foreach($phone_numbers as $i){
      if(preg_match('/^(\d{3})(\d{4})$/', $hash[$i],  $matches )){
        $hash[$i] = $matches[1].'-'.$matches[2];
      }
    }
    # setting these defaults to Y will send the report straight into I/LEADS
    $hash['rec_reviewed'] = (isset($hash['rec_reviewed']) ? $hash['rec_reviewed'] : 'Y');
    $hash['rec_completed'] = (isset($hash['rec_completed']) ? $hash['rec_completed'] : 'Y');

    if(!$hash['crime_typ']){
      $this->messages[] = "You did not enter in a crime type!"; 
    }elseif(!$hash['crime_from_date']){
      $this->messages[] = "You did not enter in a correctly formatted date for when the crime occurred!"; 
    }elseif(!$hash['bus_vic_name'] && !$hash['vic_last_name'] && !$hash['vic_first_name']){
      $this->messages[] = "You did not enter in all the required victim information!"; 
    }elseif(!$hash['crime_narr']){
      $this->messages[] = "You did not enter in a statement about what happened!"; 
    }elseif(!$hash['vic_sig']){
      $this->messages[] = "You did not sign your report!"; 
    }elseif(!$hash['certify']){
      $this->messages[] = "You did not certify that all the information that you entered in is accurate to your best ability."; 
    }else{
      if($this->save($hash)) {
        $this->messages[] = "You have successfully created a Crime Report!"; 
        return $id;
      }else{
        $this->messages[] = "There was a problem!"; 
      }
    }
  }

  function list_colors($args){
    $colors = array(
      'Aluminum' => 'SIL',
      'Amethyst (Purple)' => 'AME',
      'Beige' => 'BGE',
      'Black' => 'BLK',
      'Blue' => 'BLU',
      'Dark Blue' => 'DBL',
      'Light Blue' => 'LBL',
      'Bronze' => 'BRZ',
      'Brown' => 'BRO',
      'Burgundy (purple)' => 'MAR',
      'Camouflage' => 'CAM',
      'Chrome' => 'COM',
      'Copper' => 'CPR',
      'Cream' => 'CRM',
      'Gold' => 'GLD',
      'Gray' => 'GRY',
      'Green' => 'GRN',
      'Dark Green' => 'DGR',
      'Light Green' => 'LGR',
      'Ivory' => 'CRM',
      'Lavender' => 'LAV',
      'Maroon' => 'MAR',
      'Mauve (purple)' => 'MVE',
      'Multicolored' => 'MUL',
      'Orange' => 'ONG',
      'Pink' => 'PNK',
      'Purple' => 'PLE',
      'Red' => 'RED',
      'Silver' => 'SIL',
      'Stainless Steel' => 'COM',
      'Tan' => 'TAN',
      'Taupe (brown)' => 'TPE',
      'Teal (green)' => 'TEA',
      'Turquoise (blue)' => 'TRQ',
      'White' => 'WHI',
      'Yellow' => 'YEL',
    );
    return $colors;
  }

  function list_colors2($args){
    $colors = array(
      'ALU' => 'Aluminum',
      'AME' => 'Amethyst (Purple)',
      'BGE' => 'Beige',
      'BLK' => 'Black',
      'BLU' => 'Blue',
      'DBL' => 'Dark Blue',
      'LBL' => 'Light Blue',
      'BRZ' => 'Bronze',
      'BRO' => 'Brown',
      'BUR' => 'Burgundy (purple)',
      'CAM' => 'Camouflage',
      'COM' => 'Chrome',
      'CPR' => 'Copper',
      'CRM' => 'Cream',
      'GLD' => 'Gold',
      'GRY' => 'Gray',
      'GRN' => 'Green',
      'DGR' => 'Dark Green',
      'LGR' => 'Light Green',
      'IVR' => 'Ivory',
      'LAV' => 'Lavender',
      'MAR' => 'Maroon',
      'MVE' => 'Mauve (purple)',
      'MUL' => 'Multicolored',
      'ONG' => 'Orange',
      'PNK' => 'Pink',
      'PLE' => 'Purple',
      'RED' => 'Red',
      'SIL' => 'Silver',
      'STS' => 'Stainless Steel',
      'TAN' => 'Tan',
      'TPE' => 'Taupe (brown)',
      'TEA' => 'Teal (green)',
      'TRQ' => 'Turquoise (blue)',
      'WHI' => 'White',
      'YEL' => 'Yellow',
    );
    return $colors;
  }

  function list_vehicle_styles($args){
      #'AM' => 'Ambulance',
      #'BZ' => 'Biohazard',
      #'CH' => 'Coach',
      #'CV' => 'Convertible',
      #'HT' => 'Hardtop',
      #'2T' => 'Hardtop, 2-door',
      #'4T' => 'Hardtop, 4-door',
      #'2H' => 'Hatchback, 2-door',
      #'4H' => 'Hatchback, 4-door',
      #'HB' => 'Hatchback/Fastback',
      #'HR' => 'Hearse',
      #'LV' => 'Law Enforcement',
      #'LM' => 'Limousine',
      #'RH' => 'Retractable Hardtop',
      #'RD' => 'Roadster',
      #'SQ' => 'Search and Rescue',
      #'TO' => 'Touring Car'
    $vehicle_styles = array(
      'CP' => 'Coupe',
      'MD' => 'Moped',
      'MC' => 'Motorcycle',
      'MS' => 'Motorscooter',
      'PK' => 'Pickup',
      '2D' => 'Sedan, 2-door',
      '4D' => 'Sedan, 4-door',
      'SD' => 'Sedan',
      'SW' => 'Station Wagon',
      'LL' => 'SUV',
      'VN' => 'Van',
    );
    return $vehicle_styles;
  }
}

?>

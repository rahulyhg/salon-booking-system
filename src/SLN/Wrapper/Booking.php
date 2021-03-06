<?php

class SLN_Wrapper_Booking extends SLN_Wrapper_Abstract
{
    function getAmount()
    {
        $post_id = $this->getId();
        $ret     = apply_filters('sln_booking_amount', get_post_meta($post_id, '_sln_booking_amount', true));
        $ret     = number_format(empty($ret) ? 0 : floatval($ret), 2);

        return $ret;
    }

    function getDeposit()
    {
        $post_id = $this->getId();
        $ret     = apply_filters('sln_booking_deposit', get_post_meta($post_id, '_sln_booking_deposit', true));
        $ret     = number_format(empty($ret) ? 0 : floatval($ret), 2);

        return $ret;
    }


    function getFirstname()
    {
        $post_id = $this->getId();

        return apply_filters('sln_booking_firstname', get_post_meta($post_id, '_sln_booking_firstname', true));
    }

    function getLastname()
    {
        $post_id = $this->getId();

        return apply_filters('sln_booking_lastname', get_post_meta($post_id, '_sln_booking_lastname', true));
    }
    function getDisplayName(){
        return $this->getFirstname().' '.$this->getLastname();
    }
    function getEmail()
    {
        $post_id = $this->getId();

        return apply_filters('sln_booking_email', get_post_meta($post_id, '_sln_booking_email', true));
    }

    function getPhone()
    {
        $post_id = $this->getId();

        return apply_filters('sln_booking_phone', get_post_meta($post_id, '_sln_booking_phone', true));
    }

    function getAddress()
    {
        $post_id = $this->getId();

        return apply_filters('sln_booking_address', get_post_meta($post_id, '_sln_booking_address', true));
    }


    function getTime()
    {
        $post_id = $this->getId();

        return apply_filters('sln_booking_time', new SLN_DateTime(get_post_meta($post_id, '_sln_booking_time', true)));
    }

    function getDate()
    {
        $post_id = $this->getId();

        return apply_filters('sln_booking_date', new SLN_DateTime(get_post_meta($post_id, '_sln_booking_date', true)));
    }

    function getDuration()
    {
        $post_id = $this->getId();
        $ret     = apply_filters('sln_booking_duration', get_post_meta($post_id, '_sln_booking_duration', true));

        if(empty($ret)){
            $ret = '00:00';
        }
        $ret     = SLN_Func::filter($ret, 'time');
        if($ret == '00:00'){
            $ret = $this->evalDuration();
        } 
        return new SLN_DateTime('1970-01-01 ' . $ret);
    }

    function evalDuration(){
        $h = 0;
        $i = 0;
        SLN_Plugin::addLog(__CLASS__.' eval duration of'.$this->getId());
        foreach($this->getServices() as $s){
            $d = $s->getDuration();
            $h = $h + intval($d->format('H'));
            $i = $i + intval($d->format('i'));
            SLN_Plugin::addLog(' - service '.$s.' +'.$d->format('H:i'));
        }
        $i += $h*60;
        if($i == 0)
            $i = 60;
        $str = SLN_Func::convertToHoursMins($i);
        update_post_meta($this->getId(), '_sln_booking_duration', $str);
        return $str;
    }

    function evalTotal(){
        $t = 0;
        SLN_Plugin::addLog(__CLASS__.' eval total of'.$this->getId());
        foreach($this->getServices() as $s){
            $d = $s->getPrice();
            $t += $d;
            SLN_Plugin::addLog(' - service '.$s.' +'.$d);
        }
        update_post_meta($this->getId(), '_sln_booking_amount', $t);
        return $t;
    }


    function hasAttendant(SLN_Wrapper_Attendant $attendant)
    {
        return $this->getAttendantId() == $attendant->getId();
    }

    function hasService(SLN_Wrapper_Service $service)
    {
        return in_array($service->getId(), $this->getServicesIds());
    }
    function getAttendantId(){
        return apply_filters('sln_booking_attendant', get_post_meta($this->getId(), '_sln_booking_attendant', true));
    }
    function getAttendant(){
        if($id = $this->getAttendantId()){
            return new SLN_Wrapper_Attendant($id);
        }
    }
    function getServicesIds()
    {
        $post_id = $this->getId();
        $ret     = apply_filters('sln_booking_services', get_post_meta($post_id, '_sln_booking_services', true));

        return empty($ret) ? array() : $ret;
    }
    function getServices(){
        $ret = array();
        foreach($this->getServicesIds() as $id){
            $ret[] = new SLN_Wrapper_Service($id);
        }
        return $ret;
    }

    function getStatus()
    {
        return $this->object->post_status;
    }

    function hasStatus($status)
    {
        return $this->getStatus() == $status;
    }

    /**
     * @param $status
     * @return $this
     */
    function setStatus($status)
    {
        $post = array();
        $post['ID'] = $this->getId();
        $post['post_status'] = $status;
        wp_update_post( $post );
        return $this;
    }

    function getTitle()
    {
        return $this->object->post_title;
    }

    function getNote()
    {
        $post_id = $this->getId();

        return apply_filters(
            'sln_booking_note',
            get_post_meta($post_id, '_sln_booking_note', true)
        );
    }
    function getAdminNote()
    {
        $post_id = $this->getId();

        return apply_filters(
            'sln_booking_admin_note',
            get_post_meta($post_id, '_sln_booking_admin_note', true)
        );
    }


    function getTransactionId()
    {
        $post_id = $this->getId();

        return apply_filters(
            'sln_booking_transaction_id',
            get_post_meta($post_id, '_sln_booking_transaction_id', true)
        );
    }
    function getStartsAt(){
        return new SLN_DateTime($this->getDate()->format('Y-m-d').' '.$this->getTime()->format('H:i'));
    }
    function getEndsAt(){
        $start = $this->getStartsAt(); 
        //SLN_Plugin::addLog($this->getId().' duration '.$this->getDuration()->format('H:i'));
        $minutes = SLN_Func::getMinutesFromDuration($this->getDuration());
        //SLN_Plugin::addLog($this->getId().' duration '.$minutes.' minutes');
        if($minutes == 0) $minutes = 60;
        $start->modify('+'.$minutes.' minutes');
        return $start;
    }

    function getRemind()
    {
        $post_id = $this->getId();

        return apply_filters('sln_booking_remind', get_post_meta($post_id, '_sln_booking_remind', true));
    }

    function setRemind($remind)
    {
        $post_id = $this->getId();

        update_post_meta($post_id, '_sln_booking_remind', $remind);
    }

    public function getUserData(){
        $this->object->post_author ? get_userdata($this->object->post_author) : null; 
    }
    public function getUserDisplayName(){
        $this->getUserData() ? $this->getUserData()->display_name : '';
    }
    public function getUserId(){
        return $this->object->post_author;
    }

    function isNew()
    {
        return strpos($this->object->post_status, 'sln-b-') !== 0;
    }


}

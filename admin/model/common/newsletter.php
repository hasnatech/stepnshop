<?php
class ModelCommonNewsletter extends Model
{
    
    public function createNewslettersubscribe()
    {
        
        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "newsletter_subscribe'");
        if ($query->num_rows == 0) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "newsletter_subscribe`(`newsletter_id` int(11) NOT NULL AUTO_INCREMENT,`newsletter_email` varchar(255) NOT NULL,PRIMARY KEY (`newsletter_id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
        }
        
    }
    
    public function getNewsLetter($data = array())
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "newsletter_subscribe order by newsletter_id desc";
        
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        
        return $query->rows;
    }
    
    public function NewsletterDelete($data)
    {
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "newsletter_subscribe WHERE newsletter_email = '" . $data . "'");
        
    }
    
    
    public function getTotalNewsLetter()
    {
        
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "newsletter_subscribe");
        
        return $query->row['total'];
        
    }
    
    
    public function NewsletterComplete()
    {
        
        $query = $this->db->query("SELECT newsletter_email  FROM " . DB_PREFIX . "newsletter_subscribe");
        
        return $query->rows;
        
    }
    
    
    public function NewsletterSubscribes($data)
    {
        
        $res = $this->db->query("select * from " . DB_PREFIX . "newsletter_subscribe where newsletter_email='" . $data['email'] . "'");
		
        //	echo "select * from ". DB_PREFIX ."newsletter_subscribe where newsletter_email='".$data['email']."'";
        if ($res->num_rows == 1) {
            return "Email Already Exist";
        } else {
            $this->db->query("INSERT INTO " . DB_PREFIX . "newsletter_subscribe(newsletter_email) values ('" . $data['email'] . "')");
            return "Subscription Success";
        }
        
        
    }
    
}
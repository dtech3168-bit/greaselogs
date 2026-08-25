<?php
/**
 * DeluxeSocial - AcctShop API Integration
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/Listing.php';

class AcctShopAPI {
    private $api_key = ACCTSHOP_API_KEY;
    private $api_url = ACCTSHOP_API_URL;
    private $db;
    private $listingObj;

    public function __construct() {
        $this->db = new Database();
        $this->listingObj = new Listing();
    }

    /**
     * Fetch accounts from AcctShop API
     */
    public function fetchAccounts() {
        $url = $this->api_url . "/accounts?api_key=" . $this->api_key;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        $response = json_decode($result, true);
        
        if ($response && isset($response['status']) && $response['status'] == 'success') {
            return $response['data'];
        } else {
            return false;
        }
    }

    /**
     * Sync accounts with local database
     */
    public function syncAccounts() {
        $accounts = $this->fetchAccounts();
        
        if (!$accounts) return false;
        
        $synced_count = 0;
        
        foreach ($accounts as $acc) {
            // Check if account already exists (using a unique identifier from API, e.g., external_id)
            // For this demo, we'll assume title + platform is unique for API sources
            $this->db->query("SELECT id FROM listings WHERE title = :title AND platform = :platform AND source = 'api'");
            $this->db->bind(':title', $acc['title']);
            $this->db->bind(':platform', $acc['platform']);
            $existing = $this->db->single();
            
            if (!$existing) {
                $data = [
                    'title' => $acc['title'],
                    'platform' => $acc['platform'],
                    'followers_count' => $acc['followers'],
                    'engagement_rate' => $acc['engagement'] ?? 0,
                    'niche' => $acc['niche'] ?? 'General',
                    'price' => $acc['price'],
                    'description' => $acc['description'],
                    'account_username' => $acc['username'],
                    'account_password' => $acc['password'],
                    'account_email' => $acc['email'] ?? '',
                    'is_featured' => 0,
                    'source' => 'api'
                ];
                
                if ($this->listingObj->addListing($data)) {
                    $synced_count++;
                }
            }
        }
        
        return $synced_count;
    }
}

// If run via CLI/Cron
if (php_sapi_name() == "cli") {
    $api = new AcctShopAPI();
    $count = $api->syncAccounts();
    echo "Sync completed. $count new accounts added.\n";
}
?>

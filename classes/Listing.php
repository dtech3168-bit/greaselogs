<?php
/**
 * Greaselogs - Listing Class
 * Manual social media account listings created/uploaded by admins.
 * These merge into the marketplace alongside AcctShop API products.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

class Listing {

    private $db;

    public function __construct() {
        $this->db = db();
    }

    /**
     * Get listings, optionally filtered.
     * $filters supports: status ('available'/'sold'), platform, search
     */
    public function getListings($filters = []) {

        $sql = "SELECT * FROM listings WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['platform'])) {
            $sql .= " AND platform = ?";
            $params[] = $filters['platform'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR niche LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getFeaturedListings($limit = 6) {
        $stmt = $this->db->prepare("
            SELECT * FROM listings
            WHERE is_featured = 1 AND status = 'available'
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getListingById($id) {
        $stmt = $this->db->prepare("SELECT * FROM listings WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function addListing($data) {

        $stmt = $this->db->prepare("
            INSERT INTO listings
            (title, platform, followers_count, engagement_rate, niche, price,
             description, image, source, account_username, account_password,
             account_email, is_featured, status)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 'manual', ?, ?, ?, ?, 'available')
        ");

        return $stmt->execute([
            $data['title']            ?? '',
            $data['platform']         ?? '',
            $data['followers_count']  ?? 0,
            $data['engagement_rate']  ?? null,
            $data['niche']            ?? null,
            $data['price']            ?? 0,
            $data['description']      ?? '',
            $data['image']            ?? null,
            $data['account_username'] ?? null,
            $data['account_password'] ?? null,
            $data['account_email']    ?? null,
            $data['is_featured']      ?? 0,
        ]);
    }

    public function updateListing($id, $data) {

        // Only overwrite the image if a new one was uploaded
        $imageSql = isset($data['image']) ? ", image = ?" : "";

        $sql = "
            UPDATE listings SET
                title = ?, platform = ?, followers_count = ?, engagement_rate = ?,
                niche = ?, price = ?, description = ?, account_username = ?,
                account_password = ?, account_email = ?, is_featured = ?
                $imageSql
            WHERE id = ?
        ";

        $params = [
            $data['title']            ?? '',
            $data['platform']         ?? '',
            $data['followers_count']  ?? 0,
            $data['engagement_rate']  ?? null,
            $data['niche']            ?? null,
            $data['price']            ?? 0,
            $data['description']      ?? '',
            $data['account_username'] ?? null,
            $data['account_password'] ?? null,
            $data['account_email']    ?? null,
            $data['is_featured']      ?? 0,
        ];

        if (isset($data['image'])) {
            $params[] = $data['image'];
        }

        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteListing($id) {
        $stmt = $this->db->prepare("DELETE FROM listings WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function markAsSold($id) {
        $stmt = $this->db->prepare("UPDATE listings SET status = 'sold' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function toggleFeatured($id) {
        $stmt = $this->db->prepare("UPDATE listings SET is_featured = NOT is_featured WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function createOrder($data) {
        $stmt = $this->db->prepare("
            INSERT INTO orders (user_id, product_id, amount, status, created_at)
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['product_id'],
            $data['amount']
        ]);
    }
}

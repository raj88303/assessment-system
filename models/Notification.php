<?php

/**
 * Class Notification
 *
 * Handles operations related to notifications, including sending notifications to users
 * and retrieving user-specific notifications.
 */
class Notification
{
    private $conn;
    private $table_name = "notifications";

    public $notification_id;
    public $user_id;
    public $message;
    public $status;

    /**
     * Notification constructor.
     *
     * @param PDO $db A PDO database connection.
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Send a notification to a specific user.
     *
     * Inserts a new notification record into the database.
     *
     * @param int $user_id The ID of the user to whom the notification is being sent.
     * @param string $message The message content of the notification.
     * @return bool True on success, False on failure.
     */
    public function sendNotification($user_id, $message)
    {
        $query = "INSERT INTO " . $this->table_name . " SET user_id=:user_id, message=:message";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':message', $message);

        return $stmt->execute();
    }

    /**
     * Retrieve all notifications for a specific user.
     *
     * Retrieves all notification records associated with a given user ID, ordered by the creation date.
     *
     * @param int $user_id The ID of the user whose notifications are being retrieved.
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function getUserNotifications($user_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }
}

<?php

/**
 * Class Feedback
 *
 * Handles operations related to feedback, including creating and reading feedback entries
 * associated with specific assessments.
 */
class Feedback
{
    private $conn;
    private $table_name = "feedback";

    public $feedback_id;
    public $assessment_id;
    public $user_id;
    public $comments;
    public $role;

    /**
     * Feedback constructor.
     *
     * @param PDO $db A PDO database connection.
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Create a new feedback entry.
     *
     * Inserts a new feedback record into the database.
     *
     * @return bool True on success, False on failure.
     */
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " SET assessment_id=:assessment_id, user_id=:user_id, comments=:comments, role=:role";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':assessment_id', $this->assessment_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':comments', $this->comments);
        $stmt->bindParam(':role', $this->role);

        return $stmt->execute();
    }

    /**
     * Read feedback entries for a specific assessment.
     *
     * Retrieves all feedback records associated with a given assessment ID.
     *
     * @param int $assessment_id The ID of the assessment for which feedback is being retrieved.
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function read($assessment_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE assessment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $assessment_id);
        $stmt->execute();
        return $stmt;
    }
}

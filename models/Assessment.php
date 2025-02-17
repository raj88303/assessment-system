<?php

/**
 * Class Assessment
 *
 * Handles operations related to employee assessments, including creating, reading,
 * and fetching all assessments with user information.
 */
class Assessment
{
    private $conn;
    private $table_name = "assessments";

    public $assessment_id;
    public $user_id;
    public $period;
    public $total_score;
    public $supervisor_score;
    public $hr_score;

    /**
     * Assessment constructor.
     *
     * @param PDO $db A PDO database connection.
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Create a new assessment.
     *
     * Inserts a new assessment record into the database and sets the assessment_id
     * to the last inserted ID.
     *
     * @return bool True on success, False on failure.
     */
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, period=:period, total_score=:total_score, 
                      supervisor_score=:supervisor_score, hr_score=:hr_score";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':period', $this->period);
        $stmt->bindParam(':total_score', $this->total_score);
        $stmt->bindParam(':supervisor_score', $this->supervisor_score);
        $stmt->bindParam(':hr_score', $this->hr_score);

        if ($stmt->execute()) {
            $this->assessment_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Read all assessments for a specific user.
     *
     * Retrieves all assessment records associated with a given user ID.
     *
     * @param int $user_id The ID of the user whose assessments are being retrieved.
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function read($user_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Get all assessments with associated user information.
     *
     * Retrieves all assessment records along with the associated user's name.
     *
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function getAllAssessments()
    {
        $query = "SELECT a.*, u.name FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}

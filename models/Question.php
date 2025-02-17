<?php

/**
 * Class Question
 *
 * Handles operations related to questions, including creating, reading, updating,
 * and deleting questions associated with specific designations and periods.
 */
class Question
{
    private $conn;
    private $table_name = "questions";

    public $question_id;
    public $question_text;
    public $designation;
    public $period;

    /**
     * Question constructor.
     *
     * @param PDO $db A PDO database connection.
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Create a new question.
     *
     * Inserts a new question record into the database.
     *
     * @return bool True on success, False on failure.
     */
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " SET question_text=:question_text, designation=:designation, period=:period";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':question_text', $this->question_text);
        $stmt->bindParam(':designation', $this->designation);
        $stmt->bindParam(':period', $this->period);

        return $stmt->execute();
    }

    /**
     * Read all questions.
     *
     * Retrieves all question records from the database.
     *
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function readAll()
    {
        $where = [];
        if ($_GET['action'] == 'filter') {
            if ($_GET['department']!='') {
                $where[] = "a.department = '" . $_GET['department'] . "'";
            }

            if ($_GET['status']!='' && $_GET['status'] != 'all') {
                $status = intval($_GET['status']); // Ensure it's an integer
                $where[] = "a.status = $status";
            }
        }
        if (count($where) > 0) {
           $where = 'WHERE '.implode(' AND ', $where);
        } else {
            $where = '';
        }
        
        $query = "SELECT a.*, b.name FROM " . $this->table_name.' AS a 
        LEFT JOIN users AS b ON a.specific_user = b.user_id
        '.$where ." 
        ORDER BY a.department ASC, a.question_id ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function changeStatus($question_id, $status)
    {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE question_id = :question_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':question_id', $question_id);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    }
    /**
     * Get all unique designations from the users table.
     *
     * Retrieves all unique designations from the users table for use in question creation.
     *
     * @return array An associative array containing the designations.
     */
    public function getDesignations()
    {
        $query = "SELECT DISTINCT designation FROM users";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getDepartments()
    {
        $query = "SELECT DISTINCT department FROM users";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function loadQuestion($question_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE question_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $question_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Read questions by designation and period.
     *
     * Retrieves all questions associated with a specific designation and period.
     *
     * @param string $designation The designation for which to fetch questions.
     * @param string $period The period in 'Y-m-d' format.
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function readByDesignationAndPeriod($designation, $period)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE designation = ? AND period = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $designation);
        $stmt->bindParam(2, $period);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Get all unique designations from the users table.
     *
     * Retrieves all unique designations from the users table.
     *
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function getAllDesignationsFromUsers()
    {
        $query = "SELECT DISTINCT designation FROM users";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Get questions by designation.
     *
     * Retrieves all questions associated with a specific designation.
     *
     * @param string $designation The designation for which to fetch questions.
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function getAssessmentQuestion()
    {
        $query = "SELECT * FROM questions WHERE specific_user = :user_id AND status = 1 ORDER BY question_id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        // Fetch the questions assigned to the specific_user
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // If there are questions for the specific_user, show them and exit
        if (!empty($questions))
            return $questions;
        else 
        {
            $query = "SELECT * FROM questions WHERE department = :department AND status = 1 ORDER BY question_id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':department', $_SESSION['department']);
            $stmt->execute();
            
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($questions))
                return $questions;
        }
        
        return ;
    }

    /**
     * Create a new question with specified details.
     *
     * Inserts a new question record into the database.
     *
     * @param string $question_text The text of the question.
     * @param string $designation The designation associated with the question.
     * @param string $period The period in 'Y-m-d' format.
     * @return bool True on success, False on failure.
     */
    public function createQuestion($question_text, $department, $specificUser, $status)
    {
        $query = "INSERT INTO " . $this->table_name . " (question_text, department, specific_user, status) VALUES (:question_text, :department, :specific_user, :status)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':question_text', $question_text);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':specific_user', $specificUser);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    }

    /**
     * Update an existing question.
     *
     * Updates an existing question record in the database.
     *
     * @param int $question_id The ID of the question to update.
     * @param string $question_text The new text of the question.
     * @param string $designation The new designation associated with the question.
     * @param string $period The new period in 'Y-m-d' format.
     * @return bool True on success, False on failure.
     */
    public function updateQuestion($question_id, $question_text, $department, $specificUser, $status)
    {
        $query = "UPDATE " . $this->table_name . " SET question_text = :question_text, department = :department, specific_user = :specific_user, status = :status WHERE question_id = :question_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':question_id', $question_id);
        $stmt->bindParam(':question_text', $question_text);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':specific_user', $specificUser);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    }

    /**
     * Delete a question by ID.
     *
     * Deletes a question record from the database.
     *
     * @param int $question_id The ID of the question to delete.
     * @return bool True on success, False on failure.
     */
    public function deleteQuestion($question_id)
    {
        $sql = "SELECT COUNT(*) FROM assessment_details WHERE criterion = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $question_id, PDO::PARAM_INT);
        $stmt->execute();
        // Get the count
        $count = $stmt->fetchColumn();
        if($count > 0)
            return false;
        
        $query = "DELETE FROM " . $this->table_name . " WHERE question_id = :question_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function getEmployeesByDesignation($designation)
    {
        $query = "SELECT user_id FROM users WHERE designation = :designation";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':designation', $designation);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

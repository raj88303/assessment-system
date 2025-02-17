<?php
/**
 * Class User
 *
 * Handles user-related operations including login, registration, password reset,
 * and fetching user details by ID.
 */
class User
{
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $department;
    public $designation;
    public $supervisor_id;

    /**
     * User constructor.
     *
     * @param PDO $db A PDO database connection.
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Login a user.
     *
     * Verifies the user's credentials and logs them in if the email and password match.
     *
     * @return bool True on successful login, False otherwise.
     */
    public function login()
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($this->password, $row['password'])) {
                $this->user_id = $row['user_id'];
                $this->name = $row['name'];
                $this->role = $row['role'];
                $this->designation = $row['designation'];
                $this->department = $row['department'];
                $this->supervisor_id = $row['supervisor_idd'];
                return true;
            }
        }
        return false;
    }

    /**
     * Register a new user.
     *
     * Inserts a new user record into the database with hashed password.
     *
     * @return bool True on successful registration, False otherwise.
     */
    public function register()
    {
        
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, email=:email, password=:password, role=:role, department=:department, designation=:designation, supervisor_id = :supervisor_id";
        $stmt = $this->conn->prepare($query);
        
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':department', $this->department);
        $stmt->bindParam(':designation', $this->designation);
        $stmt->bindParam(':supervisor_id', $this->supervisor_id);
        
        return $stmt->execute();
    }
    
    public function updateUser()
    {
        if($this->password != '')
            $query = "UPDATE users SET name = :name, password = :password, role = :role, designation = :designation, department = :department, supervisor_id = :supervisor_id WHERE user_id = :user_id"; 
        else
            $query = "UPDATE users SET name = :name, role = :role, designation = :designation, department = :department, supervisor_id = :supervisor_id WHERE user_id = :user_id"; 
        
        $stmt = $this->conn->prepare($query);
        
        if ($this->password != ''){
            $this->password = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $this->password);
        }
        $stmt->bindParam(':name', $this->name);
        
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':department', $this->department);
        $stmt->bindParam(':designation', $this->designation);
        $stmt->bindParam(':supervisor_id', $this->supervisor_id);
        
        $stmt->bindParam(':user_id', $this->user_id);

        return $stmt->execute();
    }

    /**
     * Send a password reset email.
     *
     * Generates a password reset token and sends a reset email to the user.
     * (The actual email sending is simplified here.)
     *
     * @return bool True if the email exists and the reset token was generated, False otherwise.
     */
    public function sendPasswordReset()
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $reset_token = bin2hex(random_bytes(16)); // Generate a random token
            // Here you would send an email with the reset token link
            // For simplicity, we're just returning true
            return true;
        }
        return false;
    }

    /**
     * Get a user's details by their ID.
     *
     * Retrieves the user details associated with a specific user ID.
     *
     * @param int $user_id The ID of the user.
     * @return array|false The user details as an associative array, or False if the user is not found.
     */
    public function getUserById($user_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        // Fetch the user data
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user;
    }
    
    public function deleteUser($user_id)
    {
        if($user_id == $_SESSION['user_id'])
            return false;
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function readAll()
    {
        $where = '';
        if ($_GET['action'] == 'filter') {
            if ($_GET['department']!='')
                $where = "WHERE department = '" . $_GET['department'] . "'";
        }
        $query = "SELECT * FROM " . $this->table_name.' '.$where ." ORDER BY department ASC, name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function getSupervisors()
    {
        $query = "SELECT * FROM users WHERE role = 'Supervisor' || role = 'Admin' || role = 'HR'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

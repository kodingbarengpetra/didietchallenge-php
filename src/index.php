<?php

include "../vendor/autoload.php";

class User {
    public $id;
    public $name;
}

class Storage {
    
    private $list;
    
    public function __construct() {
        $this->list = new SplDoublyLinkedList();
    }
    
    public function addUser(User $user): User {
        $newId = $this->getMaxId();
        $user->id = $newId;
        $this->list->push($user);
        return $user;
    }
    
    public function deleteUser(int $id): boolean {
        
    }
    
    public function getUsers(): array {
        $users = [];
        $this->list->rewind();
        while ($this->list->valid()){
            $user = $this->list->current();
            $users[] = $user;
            $this->list->next();
        }
        return $users;
    }
    
    public function getUserById(int $id): User {
        
    }
    
    private function getMaxId(): int {
        if ($this->list->isEmpty()) {
            return 1;
        }
        $lastElmt = $this->list->top();
        return $lastElmt->id + 1;
    }
}

class JsonView {
    
    public function error(string $message) {
        
    }
    
    public function viewUsers(array $users): string {
        $array = array_map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
            ];
        }, $users);
        return json_encode($array);
    }
    
    public function viewUser(User $user) : string {
        return json_encode([
            'id' => $user->id,
            'name' => $user->name,
        ]);
        
    }
}

class Controller {
    private $requestCounter = 0;
    private $storage;
    private $view;
    
    public function __construct() {
        $this->storage = new Storage();
        $this->view = new JsonView();
    }
    
    public function run(Psr\Http\Message\ServerRequestInterface $request) {
        $this->requestCounter += 1;
        echo "Request #{$this->requestCounter}\n";
        $method = $request->getMethod();
        switch ($method) {
            case 'GET':
                $id = $_GET['id'] ?? null;
                if (!is_null($id)) {
                    $content = $this->getUserById($id);
                } else {
                    $content = $this->getAllUsers();
                }
                break;
            case 'POST':
                $data = $request->getParsedBody();
                $content = $this->createUser($data);
                break;
            case 'DELETE':
                $id = $_GET['id'];
                $content = $this->removeUser($id);
                break;
        }
        return new React\Http\Message\Response(
            200,
            ['Content-Type' => 'text/plain'],
            $content
        );
    }
    
    public function getAllUsers() {
        $users = $this->storage->getUsers();
        return $this->view->viewUsers($users);
    }
    
    public function getUserById($id) {
        echo "getUserById";
    }
    
    public function createUser(array $data) {
        $user = new User();
        $user->name = $data['name'];
        $storedUser = $this->storage->addUser($user);
        return $this->view->viewUser($storedUser);
    }
    
    public function removeUser($id) {
        echo "removeUser";
    }
}

//GET /users
//GET /users?id=1
//PUT /users
//DEL /users?id=1

$loop = React\EventLoop\Factory::create();

$controller = new Controller();

$server = new React\Http\Server($loop, function (Psr\Http\Message\ServerRequestInterface $request) use ($controller)  {
    $response = $controller->run($request);
    return $response;
});

$socket = new React\Socket\Server(8080, $loop);
$server->listen($socket);

echo "Server running at http://127.0.0.1:8080\n";

$loop->run();
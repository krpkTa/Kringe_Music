<?php
class MongoDBConnection {
    private $manager;
    private $database = 'KringeMusicNews';
    
    public function __construct($uri = "mongodb://localhost:27017") {
        try {
            $this->manager = new MongoDB\Driver\Manager($uri);
            
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            $this->manager->executeCommand('admin', $command);
            
        } catch (Exception $e) {
            throw new Exception('MongoDB connection failed: ' . $e->getMessage());
        }
    }
    
    public function getManager() {
        return $this->manager;
    }
    
    public function getDatabaseName() {
        return $this->database;
    }
    
    public function insert($collection, $document) {
        $bulk = new MongoDB\Driver\BulkWrite;
        
        $document['_id'] = new MongoDB\BSON\ObjectId();
        $document['created_at'] = new MongoDB\BSON\UTCDateTime();
        
        $id = $bulk->insert($document);
        $namespace = $this->database . '.' . $collection;
        
        $result = $this->manager->executeBulkWrite($namespace, $bulk);
        return $id;
    }
    
    public function find($collection, $filter = [], $options = []) {
        $query = new MongoDB\Driver\Query($filter, $options);
        $namespace = $this->database . '.' . $collection;
        
        $cursor = $this->manager->executeQuery($namespace, $query);
        return iterator_to_array($cursor);
    }
    
    public function update($collection, $filter, $update, $options = []) {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update($filter, $update, $options);
        $namespace = $this->database . '.' . $collection;
        
        $result = $this->manager->executeBulkWrite($namespace, $bulk);
        return $result->getModifiedCount();
    }
    
    public function delete($collection, $filter, $options = []) {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->delete($filter, $options);
        $namespace = $this->database . '.' . $collection;
        
        $result = $this->manager->executeBulkWrite($namespace, $bulk);
        return $result->getDeletedCount();
    }
}
?>
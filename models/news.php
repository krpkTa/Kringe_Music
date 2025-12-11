<?php
require_once 'php_scripts/mongo_connect.php';

class NewsModel {
    private $mongo;
    private $collection = 'news';
    
    public function __construct() {
        $this->mongo = new MongoDBConnection();
    }
    
    public function getAllNews($page = 1, $limit = 3) {
        $options = [
            'sort' => ['created_at' => -1],
            'skip' => ($page - 1) * $limit,
            'limit' => $limit
        ];
        
        $news = $this->mongo->find($this->collection, [], $options);
        
        $result = [];
        foreach ($news as $item) {
            $result[] = $this->bsonToArray($item);
        }
        
        return $result;
    }
    
    public function getNewsById($id) {
        try {
            $filter = ['_id' => new MongoDB\BSON\ObjectId($id)];
            $news = $this->mongo->find($this->collection, $filter);
            
            if (count($news) > 0) {
                return $this->bsonToArray($news[0]);
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createNews($newsData) {
        
        $newsData['views'] = 0;
        $newsData['likes'] = 0;
        $newsData['comments'] = [];
        $newsData['is_published'] = true;
        $newsData['tags'] = $newsData['tags'] ?? [];
        
        return $this->mongo->insert($this->collection, $newsData);
    }
    
    public function incrementViews($id) {
        $filter = ['_id' => new MongoDB\BSON\ObjectId($id)];
        $update = ['$inc' => ['views' => 1]];
        
        return $this->mongo->update($this->collection, $filter, $update);
    }
    
    public function searchNews($query) {
        $filter = [
            '$or' => [
                ['title' => new MongoDB\BSON\Regex($query, 'i')],
                ['content' => new MongoDB\BSON\Regex($query, 'i')],
                ['short_content' => new MongoDB\BSON\Regex($query, 'i')]
            ]
        ];
        
        $options = [
            'sort' => ['created_at' => -1],
            'limit' => 20
        ];
        
        $news = $this->mongo->find($this->collection, $filter, $options);
        
        $result = [];
        foreach ($news as $item) {
            $result[] = $this->bsonToArray($item);
        }
        
        return $result;
    }
    
    private function bsonToArray($bson) {
        $array = [];
        
        foreach ($bson as $key => $value) {
            if ($value instanceof MongoDB\BSON\ObjectId) {
                $array[$key] = (string)$value;
            } elseif ($value instanceof MongoDB\BSON\UTCDateTime) {
                $array[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
    
    public function addSampleData() {
        $sampleNews = [
            [
                'title' => 'Новый альбом Billie Eilish',
                'content' => 'Billie Eilish выпустила свой третий студийный альбом "Hit Me Hard and Soft". Альбом получил восторженные отзывы критиков.',
                'short_content' => 'Вышел долгожданный альбом Hit Me Hard and Soft',
                'author' => 'Музыкальный критик',
                'category' => 'releases',
                'tags' => ['поп', 'альбом', '2024', 'Billie Eilish'],
                'image_url' => '/images/news/billie-album.jpg',
                'featured' => true
            ],
            [
                'title' => 'Фестиваль Coachella 2024',
                'content' => 'Объявлены хедлайнеры фестиваля Coachella 2024. Среди них Lana Del Rey, Tyler The Creator и Doja Cat.',
                'short_content' => 'Объявлены хедлайнеры фестиваля',
                'author' => 'Редактор событий',
                'category' => 'events',
                'tags' => ['фестиваль', 'концерт', 'США', 'Coachella'],
                'image_url' => '/images/news/coachella.jpg',
                'featured' => true
            ],
            [
                'title' => 'Новый сингл The Weeknd',
                'content' => 'The Weeknd выпустил новый сингл в collaboration с Ariana Grande. Песня уже бьет рекорды по прослушиваниям.',
                'short_content' => 'Вышел новый сингл в collaboration с Ariana Grande',
                'author' => 'Музыкальный обозреватель',
                'category' => 'releases',
                'tags' => ['R&B', 'сингл', 'The Weeknd', 'Ariana Grande'],
                'image_url' => '/images/news/weeknd-single.jpg',
                'featured' => false
            ]
        ];
        
        foreach ($sampleNews as $news) {
            $this->createNews($news);
        }
        
        return count($sampleNews);
    }
}
?>
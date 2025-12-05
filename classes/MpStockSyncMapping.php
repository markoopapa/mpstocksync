public static function getMappings($limit = null, $offset = null)
{
    $sql = new DbQuery();
    $sql->select('*');
    $sql->from('mpstocksync_mapping', 'a');
    $sql->where('1=1');
    
    if ($limit !== null) {
        $sql->limit($limit, $offset);
    }
    
    $sql->orderBy('a.id_mapping DESC');
    
    return Db::getInstance()->executeS($sql);
}

# shmop

Structure of language-text data

| offset of hashtable end | hash size | offset size | the hash table | the actual data |
|-------------------------|-----------|-------------|----------------|-----------------|
| 2 bytes                 | 1 byte    | 1 byte      | ... byte       | ... byte        |
|                         | hash_size | offset_size |                |                 |


```injectablephp
$offset_size = ceil(log($shmop_lang_max_size, 256));
$hash_size = min(20, log($shmop_lang_max_size, 256)*4);
```

The hash table (repeats `$count * $shmop_hashtable_multi` times):

| hash of textcode    | offset of the item  |
|---------------------|---------------------|
| (hash_size) bytes   | (offset_size) bytes |

The actual data: any bytes ...with.

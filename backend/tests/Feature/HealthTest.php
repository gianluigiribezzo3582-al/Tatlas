<?php

it('responds on the health endpoint', function () {
    $this->get('/up')->assertOk();
});

it('has postgis and pgvector available', function () {
    $extensions = collect(DB::select('select extname from pg_extension'))->pluck('extname');

    expect($extensions)->toContain('postgis', 'vector');
});

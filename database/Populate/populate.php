<?php

require __DIR__ . '/../../config/bootstrap.php';

use Core\Database\Database;
use Database\Populate\UsersPopulate;
use Database\Populate\MovieRatingsPopulate;
use Database\Populate\RatingTagsPopulate;

Database::migrate();

UsersPopulate::populate();
MovieRatingsPopulate::populate();
RatingTagsPopulate::populate();
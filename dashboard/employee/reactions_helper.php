<?php

function get_item_reactions(mysqli $con, string $itemType, array $itemIds, int $currentUserId): array
{
  $result = [];
  if (empty($itemIds)) return $result;

  $itemIds = array_map('intval', $itemIds);
  $inList  = implode(',', $itemIds);
  $userId  = (int)$currentUserId;

  // counts
  $sqlCounts = "
    SELECT item_id, reaction_type, COUNT(*) AS cnt
    FROM item_reactions
    WHERE item_type = '" . mysqli_real_escape_string($con, $itemType) . "'
      AND item_id IN ($inList)
    GROUP BY item_id, reaction_type
  ";
  $resCounts = mysqli_query($con, $sqlCounts);
  if ($resCounts) {
    while ($row = mysqli_fetch_assoc($resCounts)) {
      $id = (int)$row['item_id'];
      $type = $row['reaction_type'];
      $cnt = (int)$row['cnt'];

      if (!isset($result[$id])) {
        $result[$id] = [
          'counts' => ['like'=>0,'dislike'=>0,'heart'=>0],
          'user_reaction' => null
        ];
      }
      if (isset($result[$id]['counts'][$type])) {
        $result[$id]['counts'][$type] = $cnt;
      }
    }
  }

  // current user's reaction
  $sqlUser = "
    SELECT item_id, reaction_type
    FROM item_reactions
    WHERE item_type = '" . mysqli_real_escape_string($con, $itemType) . "'
      AND item_id IN ($inList)
      AND user_id = $userId
  ";
  $resUser = mysqli_query($con, $sqlUser);
  if ($resUser) {
    while ($row = mysqli_fetch_assoc($resUser)) {
      $id = (int)$row['item_id'];
      if (!isset($result[$id])) {
        $result[$id] = [
          'counts' => ['like'=>0,'dislike'=>0,'heart'=>0],
          'user_reaction' => null
        ];
      }
      $result[$id]['user_reaction'] = $row['reaction_type'];
    }
  }

  return $result;
}?>



<!-- </?php

/**
 * Return reactions info for a set of posts.
 *
 * Output format:
 * [
 *   post_id => [
 *     'counts' => [
 *        'like'    => int,
 *        'dislike' => int,
 *        'heart'   => int,
 *     ],
 *     'user_reaction' => 'like' | 'dislike' | 'heart' | null
 *   ],
 *   ...
 * ]
 */
function get_post_reactions(mysqli $con, array $postIds, int $currentUserId): array
{
    $result = [];

    if (empty($postIds)) {
        return $result;
    }

    // Ensure ints
    $postIds = array_map('intval', $postIds);
    $inList  = implode(',', $postIds);

    // 1) Counts per reaction_type per post
    $sqlCounts = "
        SELECT post_id, reaction_type, COUNT(*) AS cnt
        FROM post_reactions
        WHERE post_id IN ($inList)
        GROUP BY post_id, reaction_type
    ";

    $resCounts = mysqli_query($con, $sqlCounts);
    if ($resCounts) {
        while ($row = mysqli_fetch_assoc($resCounts)) {
            $pid  = (int)$row['post_id'];
            $type = $row['reaction_type'];
            $cnt  = (int)$row['cnt'];

            if (!isset($result[$pid])) {
                $result[$pid] = [
                    'counts' => [
                        'like'    => 0,
                        'dislike' => 0,
                        'heart'   => 0,
                    ],
                    'user_reaction' => null,
                ];
            }

            if (isset($result[$pid]['counts'][$type])) {
                $result[$pid]['counts'][$type] = $cnt;
            }
        }
    }

    // 2) Current user’s reaction per post
    $userId = (int)$currentUserId;
    $sqlUser = "
        SELECT post_id, reaction_type
        FROM post_reactions
        WHERE post_id IN ($inList)
          AND user_id = $userId
    ";

    $resUser = mysqli_query($con, $sqlUser);
    if ($resUser) {
        while ($row = mysqli_fetch_assoc($resUser)) {
            $pid  = (int)$row['post_id'];
            $type = $row['reaction_type'];

            if (!isset($result[$pid])) {
                $result[$pid] = [
                    'counts' => [
                        'like'    => 0,
                        'dislike' => 0,
                        'heart'   => 0,
                    ],
                    'user_reaction' => null,
                ];
            }
            $result[$pid]['user_reaction'] = $type;
        }
    }

    return $result;
} -->

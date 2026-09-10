

<!-- Bi- Directional (Gay) -->
 <?php
function normalize_user_session($u_data) {
    if (!$u_data) return null;

    // If it's a numeric array, create associative equivalents
    if (isset($u_data[4]) && isset($u_data[5]) && !isset($u_data['id'])) {
        $assoc = [
            'fullname'   => $u_data[0] ?? '',
            'user_des'   => $u_data[1] ?? '',
            'user_scale' => $u_data[2] ?? '',
            'user_res'   => $u_data[3] ?? '',
            'user_role'  => $u_data[4] ?? '',
            'id'         => $u_data[5] ?? '',
        ];
        // Merge both numeric and associative keys
        return array_merge($u_data, $assoc);
    }

    // If it's associative, create numeric equivalents too
    if (isset($u_data['id']) && !isset($u_data[5])) {
        $numeric = [
            0 => $u_data['fullname']   ?? '',
            1 => $u_data['user_des']   ?? '',
            2 => $u_data['user_scale'] ?? '',
            3 => $u_data['user_res']   ?? '',
            4 => $u_data['user_role']  ?? '',
            5 => $u_data['id']         ?? '',
        ];
        // Merge both associative and numeric keys
        return array_merge($numeric, $u_data);
    }

    // If both are already present, return as-is
    return $u_data;
}
?>

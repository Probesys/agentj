(function (t) {
// ja
t.add("This value should be false.", "false\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be true.", "true\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be of type {{ type }}.", "\u578b\u306f{{ type }}\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be blank.", "\u7a7a\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The value you selected is not a valid choice.", "\u6709\u52b9\u306a\u9078\u629e\u80a2\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("You must select at least {{ limit }} choice.|You must select at least {{ limit }} choices.", "{{ limit }}\u500b\u4ee5\u4e0a\u9078\u629e\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
t.add("You must select at most {{ limit }} choice.|You must select at most {{ limit }} choices.", "{{ limit }}\u500b\u4ee5\u5185\u3067\u9078\u629e\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
t.add("One or more of the given values is invalid.", "\u7121\u52b9\u306a\u9078\u629e\u80a2\u304c\u542b\u307e\u308c\u3066\u3044\u307e\u3059\u3002", "validators", "ja");
t.add("This field was not expected.", "\u3053\u306e\u30d5\u30a3\u30fc\u30eb\u30c9\u306f\u4e88\u671f\u3055\u308c\u3066\u3044\u307e\u305b\u3093\u3067\u3057\u305f\u3002", "validators", "ja");
t.add("This field is missing.", "\u3053\u306e\u30d5\u30a3\u30fc\u30eb\u30c9\u306f\u3001\u6b20\u843d\u3057\u3066\u3044\u307e\u3059\u3002", "validators", "ja");
t.add("This value is not a valid date.", "\u6709\u52b9\u306a\u65e5\u4ed8\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid datetime.", "\u6709\u52b9\u306a\u65e5\u6642\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid email address.", "\u6709\u52b9\u306a\u30e1\u30fc\u30eb\u30a2\u30c9\u30ec\u30b9\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The file could not be found.", "\u30d5\u30a1\u30a4\u30eb\u304c\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The file is not readable.", "\u30d5\u30a1\u30a4\u30eb\u3092\u8aad\u307f\u8fbc\u3081\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}.", "\u30d5\u30a1\u30a4\u30eb\u306e\u30b5\u30a4\u30ba\u304c\u5927\u304d\u3059\u304e\u307e\u3059({{ size }} {{ suffix }})\u3002\u6709\u52b9\u306a\u6700\u5927\u30b5\u30a4\u30ba\u306f{{ limit }} {{ suffix }}\u3067\u3059\u3002", "validators", "ja");
t.add("The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.", "\u30d5\u30a1\u30a4\u30eb\u306eMIME\u30bf\u30a4\u30d7\u304c\u7121\u52b9\u3067\u3059({{ type }})\u3002\u6709\u52b9\u306aMIME\u30bf\u30a4\u30d7\u306f{{ types }}\u3067\u3059\u3002", "validators", "ja");
t.add("This value should be {{ limit }} or less.", "{{ limit }}\u4ee5\u4e0b\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is too long. It should have {{ limit }} character or less.|This value is too long. It should have {{ limit }} characters or less.", "\u5024\u304c\u9577\u3059\u304e\u307e\u3059\u3002{{ limit }}\u6587\u5b57\u4ee5\u5185\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be {{ limit }} or more.", "{{ limit }}\u4ee5\u4e0a\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is too short. It should have {{ limit }} character or more.|This value is too short. It should have {{ limit }} characters or more.", "\u5024\u304c\u77ed\u3059\u304e\u307e\u3059\u3002{{ limit }}\u6587\u5b57\u4ee5\u4e0a\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should not be blank.", "\u7a7a\u3067\u3042\u3063\u3066\u306f\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should not be null.", "null\u3067\u3042\u3063\u3066\u306f\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be null.", "null\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not valid.", "\u6709\u52b9\u306a\u5024\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid time.", "\u6709\u52b9\u306a\u6642\u523b\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid URL.", "\u6709\u52b9\u306aURL\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The two values should be equal.", "2\u3064\u306e\u5024\u304c\u540c\u3058\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The file is too large. Allowed maximum size is {{ limit }} {{ suffix }}.", "\u30d5\u30a1\u30a4\u30eb\u306e\u30b5\u30a4\u30ba\u304c\u5927\u304d\u3059\u304e\u307e\u3059\u3002\u6709\u52b9\u306a\u6700\u5927\u30b5\u30a4\u30ba\u306f{{ limit }} {{ suffix }}\u3067\u3059\u3002", "validators", "ja");
t.add("The file is too large.", "\u30d5\u30a1\u30a4\u30eb\u306e\u30b5\u30a4\u30ba\u304c\u5927\u304d\u3059\u304e\u307e\u3059\u3002", "validators", "ja");
t.add("The file could not be uploaded.", "\u30d5\u30a1\u30a4\u30eb\u3092\u30a2\u30c3\u30d7\u30ed\u30fc\u30c9\u3067\u304d\u307e\u305b\u3093\u3067\u3057\u305f\u3002", "validators", "ja");
t.add("This value should be a valid number.", "\u6709\u52b9\u306a\u6570\u5b57\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This file is not a valid image.", "\u30d5\u30a1\u30a4\u30eb\u304c\u753b\u50cf\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This is not a valid IP address.", "\u6709\u52b9\u306aIP\u30a2\u30c9\u30ec\u30b9\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid language.", "\u6709\u52b9\u306a\u8a00\u8a9e\u540d\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid locale.", "\u6709\u52b9\u306a\u30ed\u30b1\u30fc\u30eb\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid country.", "\u6709\u52b9\u306a\u56fd\u540d\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is already used.", "\u65e2\u306b\u4f7f\u7528\u3055\u308c\u3066\u3044\u307e\u3059\u3002", "validators", "ja");
t.add("The size of the image could not be detected.", "\u753b\u50cf\u306e\u30b5\u30a4\u30ba\u304c\u691c\u51fa\u3067\u304d\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The image width is too big ({{ width }}px). Allowed maximum width is {{ max_width }}px.", "\u753b\u50cf\u306e\u5e45\u304c\u5927\u304d\u3059\u304e\u307e\u3059({{ width }}\u30d4\u30af\u30bb\u30eb)\u3002{{ max_width }}\u30d4\u30af\u30bb\u30eb\u307e\u3067\u306b\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
t.add("The image width is too small ({{ width }}px). Minimum width expected is {{ min_width }}px.", "\u753b\u50cf\u306e\u5e45\u304c\u5c0f\u3055\u3059\u304e\u307e\u3059({{ width }}\u30d4\u30af\u30bb\u30eb)\u3002{{ min_width }}\u30d4\u30af\u30bb\u30eb\u4ee5\u4e0a\u306b\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
t.add("The image height is too big ({{ height }}px). Allowed maximum height is {{ max_height }}px.", "\u753b\u50cf\u306e\u9ad8\u3055\u304c\u5927\u304d\u3059\u304e\u307e\u3059({{ height }}\u30d4\u30af\u30bb\u30eb)\u3002{{ max_height }}\u30d4\u30af\u30bb\u30eb\u307e\u3067\u306b\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
t.add("The image height is too small ({{ height }}px). Minimum height expected is {{ min_height }}px.", "\u753b\u50cf\u306e\u9ad8\u3055\u304c\u5c0f\u3055\u3059\u304e\u307e\u3059({{ height }}\u30d4\u30af\u30bb\u30eb)\u3002{{ min_height }}\u30d4\u30af\u30bb\u30eb\u4ee5\u4e0a\u306b\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
t.add("This value should be the user's current password.", "\u30e6\u30fc\u30b6\u30fc\u306e\u73fe\u5728\u306e\u30d1\u30b9\u30ef\u30fc\u30c9\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should have exactly {{ limit }} character.|This value should have exactly {{ limit }} characters.", "\u3061\u3087\u3046\u3069{{ limit }}\u6587\u5b57\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The file was only partially uploaded.", "\u30d5\u30a1\u30a4\u30eb\u306e\u30a2\u30c3\u30d7\u30ed\u30fc\u30c9\u306f\u5b8c\u5168\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("No file was uploaded.", "\u30d5\u30a1\u30a4\u30eb\u304c\u30a2\u30c3\u30d7\u30ed\u30fc\u30c9\u3055\u308c\u3066\u3044\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("No temporary folder was configured in php.ini.", "php.ini\u3067\u4e00\u6642\u30d5\u30a9\u30eb\u30c0\u304c\u8a2d\u5b9a\u3055\u308c\u3066\u3044\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("Cannot write temporary file to disk.", "\u4e00\u6642\u30d5\u30a1\u30a4\u30eb\u3092\u30c7\u30a3\u30b9\u30af\u306b\u66f8\u304d\u8fbc\u3080\u3053\u3068\u304c\u3067\u304d\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("A PHP extension caused the upload to fail.", "PHP\u62e1\u5f35\u306b\u3088\u3063\u3066\u30a2\u30c3\u30d7\u30ed\u30fc\u30c9\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002", "validators", "ja");
t.add("This collection should contain {{ limit }} element or more.|This collection should contain {{ limit }} elements or more.", "{{ limit }}\u500b\u4ee5\u4e0a\u306e\u8981\u7d20\u3092\u542b\u3093\u3067\u306a\u3051\u308c\u3070\u3044\u3051\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This collection should contain {{ limit }} element or less.|This collection should contain {{ limit }} elements or less.", "\u8981\u7d20\u306f{{ limit }}\u500b\u307e\u3067\u3067\u3059\u3002", "validators", "ja");
t.add("This collection should contain exactly {{ limit }} element.|This collection should contain exactly {{ limit }} elements.", "\u8981\u7d20\u306f\u3061\u3087\u3046\u3069{{ limit }}\u500b\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("Invalid card number.", "\u7121\u52b9\u306a\u30ab\u30fc\u30c9\u756a\u53f7\u3067\u3059\u3002", "validators", "ja");
t.add("Unsupported card type or invalid card number.", "\u672a\u5bfe\u5fdc\u306e\u30ab\u30fc\u30c9\u7a2e\u985e\u53c8\u306f\u7121\u52b9\u306a\u30ab\u30fc\u30c9\u756a\u53f7\u3067\u3059\u3002", "validators", "ja");
t.add("This is not a valid International Bank Account Number (IBAN).", "\u6709\u52b9\u306aIBAN\u30b3\u30fc\u30c9\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid ISBN-10.", "\u6709\u52b9\u306aISBN-10\u30b3\u30fc\u30c9\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid ISBN-13.", "\u6709\u52b9\u306aISBN-13\u30b3\u30fc\u30c9\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is neither a valid ISBN-10 nor a valid ISBN-13.", "\u6709\u52b9\u306aISBN-10\u30b3\u30fc\u30c9\u53c8\u306fISBN-13\u30b3\u30fc\u30c9\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid ISSN.", "\u6709\u52b9\u306aISSN\u30b3\u30fc\u30c9\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value is not a valid currency.", "\u6709\u52b9\u306a\u8ca8\u5e63\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be equal to {{ compared_value }}.", "{{ compared_value }}\u3068\u7b49\u3057\u304f\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be greater than {{ compared_value }}.", "{{ compared_value }}\u3088\u308a\u5927\u304d\u304f\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be greater than or equal to {{ compared_value }}.", "{{ compared_value }}\u4ee5\u4e0a\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be identical to {{ compared_value_type }} {{ compared_value }}.", "{{ compared_value_type }}\u3068\u3057\u3066\u306e{{ compared_value }}\u3068\u7b49\u3057\u304f\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be less than {{ compared_value }}.", "{{ compared_value }}\u672a\u6e80\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be less than or equal to {{ compared_value }}.", "{{ compared_value }}\u4ee5\u4e0b\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should not be equal to {{ compared_value }}.", "{{ compared_value }}\u3068\u7b49\u3057\u304f\u3066\u306f\u3044\u3051\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should not be identical to {{ compared_value_type }} {{ compared_value }}.", "{{ compared_value_type }}\u3068\u3057\u3066\u306e{{ compared_value }}\u3068\u7b49\u3057\u304f\u3066\u306f\u3044\u3051\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The image ratio is too big ({{ ratio }}). Allowed maximum ratio is {{ max_ratio }}.", "\u753b\u50cf\u306e\u30a2\u30b9\u30da\u30af\u30c8\u6bd4\u304c\u5927\u304d\u3059\u304e\u307e\u3059({{ ratio }})\u3002{{ max_ratio }}\u307e\u3067\u306b\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
t.add("The image ratio is too small ({{ ratio }}). Minimum ratio expected is {{ min_ratio }}.", "\u753b\u50cf\u306e\u30a2\u30b9\u30da\u30af\u30c8\u6bd4\u304c\u5c0f\u3055\u3059\u304e\u307e\u3059({{ ratio }})\u3002{{ min_ratio }}\u4ee5\u4e0a\u306b\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
t.add("The image is square ({{ width }}x{{ height }}px). Square images are not allowed.", "\u753b\u50cf\u304c\u6b63\u65b9\u5f62\u306b\u306a\u3063\u3066\u3044\u307e\u3059({{ width }}x{{ height }}\u30d4\u30af\u30bb\u30eb)\u3002\u6b63\u65b9\u5f62\u306e\u753b\u50cf\u306f\u8a31\u53ef\u3055\u308c\u3066\u3044\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The image is landscape oriented ({{ width }}x{{ height }}px). Landscape oriented images are not allowed.", "\u753b\u50cf\u304c\u6a2a\u5411\u304d\u306b\u306a\u3063\u3066\u3044\u307e\u3059({{ width }}x{{ height }}\u30d4\u30af\u30bb\u30eb)\u3002\u6a2a\u5411\u304d\u306e\u753b\u50cf\u306f\u8a31\u53ef\u3055\u308c\u3066\u3044\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The image is portrait oriented ({{ width }}x{{ height }}px). Portrait oriented images are not allowed.", "\u753b\u50cf\u304c\u7e26\u5411\u304d\u306b\u306a\u3063\u3066\u3044\u307e\u3059({{ width }}x{{ height }}\u30d4\u30af\u30bb\u30eb)\u3002\u7e26\u5411\u304d\u306e\u753b\u50cf\u306f\u8a31\u53ef\u3055\u308c\u3066\u3044\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("An empty file is not allowed.", "\u7a7a\u306e\u30d5\u30a1\u30a4\u30eb\u306f\u8a31\u53ef\u3055\u308c\u3066\u3044\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The host could not be resolved.", "\u30db\u30b9\u30c8\u3092\u89e3\u6c7a\u3067\u304d\u307e\u305b\u3093\u3067\u3057\u305f\u3002", "validators", "ja");
t.add("This value does not match the expected {{ charset }} charset.", "\u3053\u306e\u5024\u306f\u4e88\u671f\u3055\u308c\u308b\u6587\u5b57\u30b3\u30fc\u30c9\uff08{{ charset }}\uff09\u3068\u7570\u306a\u308a\u307e\u3059\u3002", "validators", "ja");
t.add("This is not a valid Business Identifier Code (BIC).", "\u6709\u52b9\u306aSWIFT\u30b3\u30fc\u30c9\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("Error", "\u30a8\u30e9\u30fc", "validators", "ja");
t.add("This is not a valid UUID.", "\u6709\u52b9\u306aUUID\u3067\u306f\u3042\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This value should be a multiple of {{ compared_value }}.", "{{ compared_value }}\u306e\u500d\u6570\u3067\u306a\u3051\u308c\u3070\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("This form should not contain extra fields.", "\u30d5\u30a3\u30fc\u30eb\u30c9\u30b0\u30eb\u30fc\u30d7\u306b\u8ffd\u52a0\u306e\u30d5\u30a3\u30fc\u30eb\u30c9\u3092\u542b\u3093\u3067\u306f\u306a\u308a\u307e\u305b\u3093\u3002", "validators", "ja");
t.add("The uploaded file was too large. Please try to upload a smaller file.", "\u30a2\u30c3\u30d7\u30ed\u30fc\u30c9\u3055\u308c\u305f\u30d5\u30a1\u30a4\u30eb\u304c\u5927\u304d\u3059\u304e\u307e\u3059\u3002\u5c0f\u3055\u306a\u30d5\u30a1\u30a4\u30eb\u3067\u518d\u5ea6\u30a2\u30c3\u30d7\u30ed\u30fc\u30c9\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
t.add("The CSRF token is invalid. Please try to resubmit the form.", "CSRF\u30c8\u30fc\u30af\u30f3\u304c\u7121\u52b9\u3067\u3059\u3001\u518d\u9001\u4fe1\u3057\u3066\u304f\u3060\u3055\u3044\u3002", "validators", "ja");
})(Translator);

/// Classe représentant une réponse d'API standardisée selon le format du backend
class ApiResponse<T> {
  final bool success;
  final T? data;
  final String? message;
  final ApiError? error;

  ApiResponse({
    required this.success,
    this.data,
    this.message,
    this.error,
  });

  factory ApiResponse.fromJson(Map<String, dynamic> json) {
    return ApiResponse(
      success: json['success'] as bool,
      data: json['data'] as T?,
      message: json['message'] as String?,
      error: json['error'] != null ? ApiError.fromJson(json['error']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> result = {'success': success};

    if (data != null) result['data'] = data;
    if (message != null) result['message'] = message;
    if (error != null) result['error'] = error!.toJson();

    return result;
  }

  bool get isSuccess => success;
  bool get isError => !success;
}

/// Classe représentant une erreur d'API selon le format du backend
class ApiError {
  final String code;
  final String message;
  final List<dynamic>? details;

  ApiError({
    required this.code,
    required this.message,
    this.details,
  });

  factory ApiError.fromJson(Map<String, dynamic> json) {
    return ApiError(
      code: json['code'] as String,
      message: json['message'] as String,
      details: json['details'] as List<dynamic>?,
    );
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> result = {
      'code': code,
      'message': message,
    };

    if (details != null) result['details'] = details;

    return result;
  }
}

/// Classe pour les réponses paginées
class PaginatedResponse<T> {
  final bool success;
  final List<T> items;
  final PaginationInfo pagination;
  final String? message;

  PaginatedResponse({
    required this.success,
    required this.items,
    required this.pagination,
    this.message,
  });

  factory PaginatedResponse.fromJson(Map<String, dynamic> json) {
    return PaginatedResponse(
      success: json['success'] as bool,
      items: (json['data']['items'] as List).cast<T>(),
      pagination: PaginationInfo.fromJson(json['data']['pagination']),
      message: json['message'] as String?,
    );
  }
}

/// Informations de pagination
class PaginationInfo {
  final int pageActuelle;
  final int totalPages;
  final int totalElements;
  final int elementsParPage;

  PaginationInfo({
    required this.pageActuelle,
    required this.totalPages,
    required this.totalElements,
    required this.elementsParPage,
  });

  factory PaginationInfo.fromJson(Map<String, dynamic> json) {
    return PaginationInfo(
      pageActuelle: json['pageActuelle'] as int,
      totalPages: json['totalPages'] as int,
      totalElements: json['totalElements'] as int,
      elementsParPage: json['elementsParPage'] as int,
    );
  }
}
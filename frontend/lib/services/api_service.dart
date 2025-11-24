import 'dart:convert';
import 'package:http/http.dart' as http;
import '../interfaces/api_service.dart';
import '../models/api_response.dart';
import '../cache/cache_manager.dart';

class HttpApiService implements ApiService {
  final String baseUrl;

  HttpApiService(this.baseUrl);

  @override
  Future<ApiResponse<Map<String, dynamic>>> post(String endpoint, Map<String, dynamic> data) async {
    final headers = await _buildHeaders();
    final response = await http.post(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
      body: jsonEncode(data),
    );

    return _handleResponse(response);
  }

  @override
  Future<ApiResponse<Map<String, dynamic>>> get(String endpoint) async {
    final headers = await _buildHeaders();
    final response = await http.get(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
    );

    return _handleResponse(response);
  }

  @override
  Future<ApiResponse<Map<String, dynamic>>> delete(String endpoint) async {
    final headers = await _buildHeaders();
    final response = await http.delete(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
    );

    return _handleResponse(response);
  }

  Future<Map<String, String>> _buildHeaders() async {
    final headers = {'Content-Type': 'application/json'};
    final authToken = await CacheManager.getAccessToken();
    if (authToken != null) {
      headers['Authorization'] = 'Bearer $authToken';
    }
    return headers;
  }

  ApiResponse<Map<String, dynamic>> _handleResponse(http.Response response) {
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return ApiResponse<Map<String, dynamic>>.fromJson(data);
  }
}
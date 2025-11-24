import '../models/api_response.dart';

abstract class ApiService {
  Future<ApiResponse<Map<String, dynamic>>> post(String endpoint, Map<String, dynamic> data);
  Future<ApiResponse<Map<String, dynamic>>> get(String endpoint);
  Future<ApiResponse<Map<String, dynamic>>> delete(String endpoint);
}
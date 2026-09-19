// Simple test to verify the testing setup
function sum(a, b) {
  return a + b;
}

describe('Example Test', () => {
  it('should add two numbers correctly', () => {
    expect(sum(1, 2)).toBe(3);
  });

  it('should handle negative numbers', () => {
    expect(sum(-1, 1)).toBe(0);
  });
});

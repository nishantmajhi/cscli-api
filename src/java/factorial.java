import java.util.Scanner;

public class factorial {
    static long factorial(int n) {
        long product = 1;
        for (int i = n; i > 1; i--) {
            product *= i;
        }
        return product;
    }

    public static void main(String[] args) {
        Scanner scanner = new Scanner(System.in);
        System.out.print("Enter a number: ");
        int num = scanner.nextInt();

        if (num < 0) {
            System.out.println("Factorial of negative number can't be calculated!");
            return;
        }

        System.out.println("Factorial of " + num + " = " + factorial(num));
    }
}
